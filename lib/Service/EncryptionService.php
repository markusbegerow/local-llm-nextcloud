<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Service;

use OCP\IConfig;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * Service for encrypting and decrypting API tokens
 */
class EncryptionService {
    private ICrypto $crypto;
    private IConfig $config;
    private LoggerInterface $logger;

    public function __construct(
        ICrypto $crypto,
        IConfig $config,
        LoggerInterface $logger
    ) {
        $this->crypto = $crypto;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Encrypt an API token
     */
    public function encryptToken(string $token): string {
        try {
            return $this->crypto->encrypt($token);
        } catch (\Exception $e) {
            $this->logger->error('Failed to encrypt API token: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw new \RuntimeException('Failed to encrypt API token');
        }
    }

    /**
     * Decrypt an API token
     */
    public function decryptToken(string $encryptedToken): string {
        try {
            return $this->crypto->decrypt($encryptedToken);
        } catch (\Exception $e) {
            $this->logger->error('Failed to decrypt API token: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw new \RuntimeException('Failed to decrypt API token');
        }
    }
}
