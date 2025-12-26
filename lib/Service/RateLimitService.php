<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Service;

use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;

/**
 * Service for rate limiting API requests
 */
class RateLimitService {
    private const MAX_REQUESTS_PER_MINUTE = 20;
    private const CACHE_KEY_PREFIX = 'localllm_ratelimit_';

    private ICache $cache;
    private LoggerInterface $logger;

    public function __construct(
        ICacheFactory $cacheFactory,
        LoggerInterface $logger
    ) {
        $this->cache = $cacheFactory->createDistributed('localllm_ratelimit');
        $this->logger = $logger;
    }

    /**
     * Check if user has exceeded rate limit
     *
     * @param string $userId
     * @return bool True if request is allowed, false if rate limit exceeded
     */
    public function checkRateLimit(string $userId): bool {
        $cacheKey = self::CACHE_KEY_PREFIX . $userId;
        $requests = $this->cache->get($cacheKey);

        if ($requests === null) {
            $requests = [];
        }

        // Clean old requests (older than 60 seconds)
        $currentTime = time();
        $requests = array_filter($requests, function ($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 60;
        });

        // Check if limit exceeded
        if (count($requests) >= self::MAX_REQUESTS_PER_MINUTE) {
            $this->logger->warning('Rate limit exceeded', [
                'userId' => $userId,
                'requestCount' => count($requests),
            ]);
            return false;
        }

        // Add current request
        $requests[] = $currentTime;
        $this->cache->set($cacheKey, $requests, 60);

        return true;
    }

    /**
     * Get remaining requests for user
     *
     * @param string $userId
     * @return int
     */
    public function getRemainingRequests(string $userId): int {
        $cacheKey = self::CACHE_KEY_PREFIX . $userId;
        $requests = $this->cache->get($cacheKey);

        if ($requests === null) {
            return self::MAX_REQUESTS_PER_MINUTE;
        }

        // Clean old requests
        $currentTime = time();
        $requests = array_filter($requests, function ($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 60;
        });

        return max(0, self::MAX_REQUESTS_PER_MINUTE - count($requests));
    }
}
