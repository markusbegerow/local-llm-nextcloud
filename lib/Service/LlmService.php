<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Service;

use OCA\LocalLlm\Db\LlmConfig;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;

/**
 * Service for interacting with LLM APIs
 */
class LlmService {
    private IClientService $clientService;
    private EncryptionService $encryptionService;
    private LoggerInterface $logger;

    public function __construct(
        IClientService $clientService,
        EncryptionService $encryptionService,
        LoggerInterface $logger
    ) {
        $this->clientService = $clientService;
        $this->encryptionService = $encryptionService;
        $this->logger = $logger;
    }

    /**
     * Call the LLM API with messages
     *
     * @param LlmConfig $config
     * @param array $messages Array of message objects with 'role' and 'content'
     * @return string The response content
     * @throws \Exception
     */
    public function chat(LlmConfig $config, array $messages): string {
        $client = $this->clientService->newClient();

        // Decrypt the API token
        $apiToken = 'ollama'; // default
        if ($config->getApiTokenEncrypted()) {
            try {
                $apiToken = $this->encryptionService->decryptToken($config->getApiTokenEncrypted());
            } catch (\Exception $e) {
                $this->logger->warning('Failed to decrypt API token, using default', [
                    'configId' => $config->getId(),
                ]);
            }
        }

        // Prepare request
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiToken,
        ];

        $data = [
            'model' => $config->getModelName(),
            'messages' => $messages,
            'temperature' => $config->getTemperature(),
            'max_tokens' => $config->getMaxTokens(),
        ];

        $timeout = $config->getRequestTimeout() / 1000; // Convert ms to seconds

        try {
            $this->logger->debug('Calling LLM API', [
                'url' => $config->getApiUrl(),
                'model' => $config->getModelName(),
                'messageCount' => count($messages),
            ]);

            $response = $client->post($config->getApiUrl(), [
                'headers' => $headers,
                'json' => $data,
                'timeout' => $timeout,
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (!isset($responseData['choices'][0]['message']['content'])) {
                $this->logger->error('Unexpected API response format', [
                    'response' => $responseData,
                ]);
                throw new \RuntimeException('Unexpected response from LLM server');
            }

            $content = $responseData['choices'][0]['message']['content'];

            $this->logger->debug('LLM API call successful', [
                'responseLength' => strlen($content),
            ]);

            return $content;

        } catch (\OCP\Http\Client\LocalServerException $e) {
            $this->logger->error('Connection error to LLM server', [
                'url' => $config->getApiUrl(),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Cannot connect to LLM server. Please check the configuration.');
        } catch (\Exception $e) {
            $this->logger->error('Error calling LLM API', [
                'url' => $config->getApiUrl(),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw new \RuntimeException('Error communicating with LLM server: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to LLM API
     *
     * @param LlmConfig $config
     * @return bool True if connection successful
     * @throws \Exception
     */
    public function testConnection(LlmConfig $config): bool {
        $testMessages = [
            ['role' => 'user', 'content' => 'Hello'],
        ];

        try {
            $this->chat($config, $testMessages);
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Prepare messages array with system prompt and conversation history
     *
     * @param LlmConfig $config
     * @param array $conversationMessages
     * @return array
     */
    public function prepareMessages(LlmConfig $config, array $conversationMessages): array {
        $messages = [];

        // Add system prompt if configured
        if ($config->getSystemPrompt()) {
            $messages[] = [
                'role' => 'system',
                'content' => $config->getSystemPrompt(),
            ];
        }

        // Add conversation messages
        foreach ($conversationMessages as $msg) {
            $messages[] = [
                'role' => $msg->getRole(),
                'content' => $msg->getContent(),
            ];
        }

        return $messages;
    }

    /**
     * Estimate token count from text
     * Simple approximation: ~4 characters per token
     *
     * @param string $text
     * @return int
     */
    public function estimateTokens(string $text): int {
        return (int) ceil(strlen($text) / 4);
    }
}
