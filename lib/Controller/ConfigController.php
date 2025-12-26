<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Controller;

use OCA\LocalLlm\Db\LlmConfig;
use OCA\LocalLlm\Db\LlmConfigMapper;
use OCA\LocalLlm\Service\EncryptionService;
use OCA\LocalLlm\Service\LlmService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ConfigController extends Controller {
    private LlmConfigMapper $configMapper;
    private EncryptionService $encryptionService;
    private LlmService $llmService;
    private LoggerInterface $logger;
    private ?string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        LlmConfigMapper $configMapper,
        EncryptionService $encryptionService,
        LlmService $llmService,
        LoggerInterface $logger,
        ?string $userId
    ) {
        parent::__construct($appName, $request);
        $this->configMapper = $configMapper;
        $this->encryptionService = $encryptionService;
        $this->llmService = $llmService;
        $this->logger = $logger;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     */
    public function getConfigs(): JSONResponse {
        try {
            $configs = $this->configMapper->findAll($this->userId, false);
            return new JSONResponse($configs);
        } catch (\Exception $e) {
            $this->logger->error('Error loading configs', [
                'userId' => $this->userId,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to load configurations'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function getConfig(int $id): JSONResponse {
        try {
            $config = $this->configMapper->find($id, $this->userId);
            return new JSONResponse($config);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Configuration not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error loading config', [
                'configId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to load configuration'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function createConfig(
        string $name,
        string $apiUrl,
        string $apiToken,
        string $modelName,
        float $temperature = 0.7,
        int $maxTokens = 2048,
        ?string $systemPrompt = null,
        int $maxHistoryMessages = 50,
        int $requestTimeout = 120000,
        bool $isDefault = false
    ): JSONResponse {
        try {
            // Validate inputs
            if ($temperature < 0 || $temperature > 2) {
                return new JSONResponse([
                    'error' => 'Temperature must be between 0 and 2'
                ], Http::STATUS_BAD_REQUEST);
            }

            if ($maxTokens < 128 || $maxTokens > 32768) {
                return new JSONResponse([
                    'error' => 'Max tokens must be between 128 and 32768'
                ], Http::STATUS_BAD_REQUEST);
            }

            // Encrypt API token
            $encryptedToken = $this->encryptionService->encryptToken($apiToken);

            // Clear default flags if this is the new default
            if ($isDefault) {
                $this->configMapper->clearDefaultFlags($this->userId);
            }

            $config = new LlmConfig();
            $config->setUserId($this->userId);
            $config->setName($name);
            $config->setApiUrl($apiUrl);
            $config->setApiTokenEncrypted($encryptedToken);
            $config->setModelName($modelName);
            $config->setTemperature($temperature);
            $config->setMaxTokens($maxTokens);
            $config->setSystemPrompt($systemPrompt ?: 'You are a helpful AI assistant integrated into Nextcloud. Help users with their tasks, answer questions, and provide insights. Keep responses clear, concise, and professional.');
            $config->setMaxHistoryMessages($maxHistoryMessages);
            $config->setRequestTimeout($requestTimeout);
            $config->setIsDefault($isDefault);
            $config->setActive(true);
            $config->setCreatedAt(time());
            $config->setUpdatedAt(time());

            $config = $this->configMapper->insert($config);

            $this->logger->info('Created new LLM config', [
                'configId' => $config->getId(),
                'userId' => $this->userId,
            ]);

            return new JSONResponse($config);
        } catch (\Exception $e) {
            $this->logger->error('Error creating config', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            return new JSONResponse([
                'error' => 'Failed to create configuration'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function updateConfig(
        int $id,
        ?string $name = null,
        ?string $apiUrl = null,
        ?string $apiToken = null,
        ?string $modelName = null,
        ?float $temperature = null,
        ?int $maxTokens = null,
        ?string $systemPrompt = null,
        ?int $maxHistoryMessages = null,
        ?int $requestTimeout = null,
        ?bool $active = null
    ): JSONResponse {
        try {
            $config = $this->configMapper->find($id, $this->userId);

            if ($name !== null) {
                $config->setName($name);
            }
            if ($apiUrl !== null) {
                $config->setApiUrl($apiUrl);
            }
            if ($apiToken !== null) {
                $encryptedToken = $this->encryptionService->encryptToken($apiToken);
                $config->setApiTokenEncrypted($encryptedToken);
            }
            if ($modelName !== null) {
                $config->setModelName($modelName);
            }
            if ($temperature !== null) {
                if ($temperature < 0 || $temperature > 2) {
                    return new JSONResponse([
                        'error' => 'Temperature must be between 0 and 2'
                    ], Http::STATUS_BAD_REQUEST);
                }
                $config->setTemperature($temperature);
            }
            if ($maxTokens !== null) {
                if ($maxTokens < 128 || $maxTokens > 32768) {
                    return new JSONResponse([
                        'error' => 'Max tokens must be between 128 and 32768'
                    ], Http::STATUS_BAD_REQUEST);
                }
                $config->setMaxTokens($maxTokens);
            }
            if ($systemPrompt !== null) {
                $config->setSystemPrompt($systemPrompt);
            }
            if ($maxHistoryMessages !== null) {
                $config->setMaxHistoryMessages($maxHistoryMessages);
            }
            if ($requestTimeout !== null) {
                $config->setRequestTimeout($requestTimeout);
            }
            if ($active !== null) {
                $config->setActive($active);
            }

            $config->setUpdatedAt(time());
            $this->configMapper->update($config);

            $this->logger->info('Updated LLM config', [
                'configId' => $id,
                'userId' => $this->userId,
            ]);

            return new JSONResponse($config);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Configuration not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error updating config', [
                'configId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to update configuration'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function deleteConfig(int $id): JSONResponse {
        try {
            $config = $this->configMapper->find($id, $this->userId);
            $this->configMapper->deleteById($id, $this->userId);

            $this->logger->info('Deleted LLM config', [
                'configId' => $id,
                'userId' => $this->userId,
            ]);

            return new JSONResponse(['success' => true]);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Configuration not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting config', [
                'configId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to delete configuration'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function testConnection(int $id): JSONResponse {
        try {
            $config = $this->configMapper->find($id, $this->userId);

            try {
                $this->llmService->testConnection($config);
                return new JSONResponse([
                    'success' => true,
                    'message' => 'Connection successful! Model is responding.'
                ]);
            } catch (\Exception $e) {
                return new JSONResponse([
                    'success' => false,
                    'message' => $e->getMessage()
                ], Http::STATUS_BAD_GATEWAY);
            }
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Configuration not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error testing connection', [
                'configId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to test connection'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function setDefault(int $id): JSONResponse {
        try {
            $config = $this->configMapper->find($id, $this->userId);

            // Clear all default flags
            $this->configMapper->clearDefaultFlags($this->userId);

            // Set this as default
            $config->setIsDefault(true);
            $config->setUpdatedAt(time());
            $this->configMapper->update($config);

            $this->logger->info('Set default LLM config', [
                'configId' => $id,
                'userId' => $this->userId,
            ]);

            return new JSONResponse($config);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Configuration not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Error setting default config', [
                'configId' => $id,
                'error' => $e->getMessage(),
            ]);
            return new JSONResponse([
                'error' => 'Failed to set default configuration'
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
