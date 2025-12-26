<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Controller;

use OCA\LocalLlm\Db\Conversation;
use OCA\LocalLlm\Db\ConversationMapper;
use OCA\LocalLlm\Db\LlmConfigMapper;
use OCA\LocalLlm\Db\Message;
use OCA\LocalLlm\Db\MessageMapper;
use OCA\LocalLlm\Service\LlmService;
use OCA\LocalLlm\Service\RateLimitService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ChatController extends Controller {
    private const MAX_MESSAGE_LENGTH = 10000;

    private ConversationMapper $conversationMapper;
    private MessageMapper $messageMapper;
    private LlmConfigMapper $configMapper;
    private LlmService $llmService;
    private RateLimitService $rateLimitService;
    private LoggerInterface $logger;
    private ?string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        ConversationMapper $conversationMapper,
        MessageMapper $messageMapper,
        LlmConfigMapper $configMapper,
        LlmService $llmService,
        RateLimitService $rateLimitService,
        LoggerInterface $logger,
        ?string $userId
    ) {
        parent::__construct($appName, $request);
        $this->conversationMapper = $conversationMapper;
        $this->messageMapper = $messageMapper;
        $this->configMapper = $configMapper;
        $this->llmService = $llmService;
        $this->rateLimitService = $rateLimitService;
        $this->logger = $logger;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     */
    public function sendMessage(?int $conversationId, string $message, ?int $configId = null): JSONResponse {
        try {
            // Input validation
            $message = trim($message);
            if (empty($message)) {
                return new JSONResponse(['error' => 'Message cannot be empty'], Http::STATUS_BAD_REQUEST);
            }

            if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
                return new JSONResponse([
                    'error' => 'Message too long. Maximum ' . self::MAX_MESSAGE_LENGTH . ' characters allowed'
                ], Http::STATUS_BAD_REQUEST);
            }

            // Rate limiting
            if (!$this->rateLimitService->checkRateLimit($this->userId)) {
                return new JSONResponse([
                    'error' => 'Too many requests. Please wait a moment and try again'
                ], Http::STATUS_TOO_MANY_REQUESTS);
            }

            // Get or create conversation
            if ($conversationId) {
                try {
                    $conversation = $this->conversationMapper->find($conversationId, $this->userId);
                } catch (DoesNotExistException $e) {
                    return new JSONResponse(['error' => 'Conversation not found'], Http::STATUS_NOT_FOUND);
                }
            } else {
                // Create new conversation
                // Use provided configId or fall back to default
                if ($configId) {
                    try {
                        $config = $this->configMapper->find($configId, $this->userId);
                    } catch (DoesNotExistException $e) {
                        return new JSONResponse(['error' => 'Configuration not found'], Http::STATUS_NOT_FOUND);
                    }
                } else {
                    $config = $this->configMapper->findDefault($this->userId);
                    if (!$config) {
                        $config = $this->configMapper->findAnyActive($this->userId);
                    }
                }

                if (!$config) {
                    return new JSONResponse([
                        'error' => 'No LLM configuration found. Please configure an LLM first.'
                    ], Http::STATUS_BAD_REQUEST);
                }

                $conversation = new Conversation();
                $conversation->setUserId($this->userId);
                $conversation->setConfigId($config->getId());
                $conversation->setName('New Conversation');
                $conversation->setCreatedAt(time());
                $conversation->setUpdatedAt(time());
                $conversation = $this->conversationMapper->insert($conversation);

                $this->logger->info('Created new conversation', [
                    'conversationId' => $conversation->getId(),
                    'userId' => $this->userId,
                ]);
            }

            // Create user message
            $userMessage = new Message();
            $userMessage->setConversationId($conversation->getId());
            $userMessage->setRole('user');
            $userMessage->setContent($message);
            $userMessage->setTokensUsed($this->llmService->estimateTokens($message));
            $userMessage->setCreatedAt(time());
            $userMessage = $this->messageMapper->insert($userMessage);

            // Get config
            $config = $this->configMapper->find($conversation->getConfigId(), $this->userId);

            // Get recent messages for context
            $recentMessages = $this->messageMapper->findRecentByConversation(
                $conversation->getId(),
                $config->getMaxHistoryMessages()
            );

            // Prepare messages for LLM
            $llmMessages = $this->llmService->prepareMessages($config, $recentMessages);

            // Call LLM API
            try {
                $responseContent = $this->llmService->chat($config, $llmMessages);
            } catch (\Exception $e) {
                $this->logger->error('LLM API error', [
                    'conversationId' => $conversation->getId(),
                    'error' => $e->getMessage(),
                ]);
                return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_GATEWAY);
            }

            // Create assistant message
            $assistantMessage = new Message();
            $assistantMessage->setConversationId($conversation->getId());
            $assistantMessage->setRole('assistant');
            $assistantMessage->setContent($responseContent);
            $assistantMessage->setTokensUsed($this->llmService->estimateTokens($responseContent));
            $assistantMessage->setCreatedAt(time());
            $assistantMessage = $this->messageMapper->insert($assistantMessage);

            // Update conversation name if it's the first exchange
            $messageCount = $this->messageMapper->countByConversation($conversation->getId());
            if ($messageCount === 2 && $conversation->getName() === 'New Conversation') {
                $conversation->setName(substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''));
            }

            // Update conversation timestamp
            $conversation->setUpdatedAt(time());
            $this->conversationMapper->update($conversation);

            $this->logger->info('Successfully processed message', [
                'conversationId' => $conversation->getId(),
            ]);

            return new JSONResponse([
                'conversationId' => $conversation->getId(),
                'userMessage' => $userMessage,
                'assistantMessage' => $assistantMessage,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in sendMessage', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            return new JSONResponse([
                'error' => 'An unexpected error occurred. Please try again later'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function getConversations(): JSONResponse {
        try {
            $conversations = $this->conversationMapper->findAll($this->userId, true, 50);

            $result = array_map(function ($conversation) {
                $messageCount = $this->messageMapper->countByConversation($conversation->getId());

                // Get first user message for preview
                $firstMessage = null;
                $messages = $this->messageMapper->findByConversation($conversation->getId(), 1);
                if (!empty($messages) && $messages[0]->getRole() === 'user') {
                    $content = $messages[0]->getContent();
                    $firstMessage = strlen($content) > 50 ? substr($content, 0, 50) . '...' : $content;
                }

                return array_merge($conversation->jsonSerialize(), [
                    'messageCount' => $messageCount,
                    'preview' => $firstMessage,
                ]);
            }, $conversations);

            return new JSONResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Error loading conversations', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to load conversations'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function getConversation(int $id): JSONResponse {
        try {
            $conversation = $this->conversationMapper->find($id, $this->userId);
            $messageCount = $this->messageMapper->countByConversation($id);

            return new JSONResponse(array_merge($conversation->jsonSerialize(), [
                'messageCount' => $messageCount,
            ]));
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Conversation not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error loading conversation', [
                'conversationId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to load conversation'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function getMessages(int $id): JSONResponse {
        try {
            // Verify user owns this conversation
            $conversation = $this->conversationMapper->find($id, $this->userId);
            $messages = $this->messageMapper->findByConversation($id);

            return new JSONResponse($messages);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Conversation not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error loading messages', [
                'conversationId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to load messages'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function deleteConversation(int $id): JSONResponse {
        try {
            $conversation = $this->conversationMapper->find($id, $this->userId);

            // Delete all messages first
            $this->messageMapper->deleteByConversation($id);

            // Delete conversation
            $this->conversationMapper->deleteById($id, $this->userId);

            $this->logger->info('Deleted conversation', [
                'conversationId' => $id,
                'userId' => $this->userId,
            ]);

            return new JSONResponse(['success' => true]);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Conversation not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting conversation', [
                'conversationId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to delete conversation'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function clearMessages(int $id): JSONResponse {
        try {
            $conversation = $this->conversationMapper->find($id, $this->userId);
            $this->messageMapper->deleteByConversation($id);

            $this->logger->info('Cleared messages', [
                'conversationId' => $id,
                'userId' => $this->userId,
            ]);

            return new JSONResponse(['success' => true]);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Conversation not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error clearing messages', [
                'conversationId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to clear messages'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function updateConversation(int $id, string $name): JSONResponse {
        try {
            $conversation = $this->conversationMapper->find($id, $this->userId);
            $conversation->setName($name);
            $conversation->setUpdatedAt(time());
            $this->conversationMapper->update($conversation);

            return new JSONResponse($conversation);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Conversation not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error updating conversation', [
                'conversationId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to update conversation'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
