<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getApiUrl()
 * @method void setApiUrl(string $apiUrl)
 * @method string|null getApiTokenEncrypted()
 * @method void setApiTokenEncrypted(?string $apiTokenEncrypted)
 * @method string getModelName()
 * @method void setModelName(string $modelName)
 * @method float getTemperature()
 * @method void setTemperature(float $temperature)
 * @method int getMaxTokens()
 * @method void setMaxTokens(int $maxTokens)
 * @method string|null getSystemPrompt()
 * @method void setSystemPrompt(?string $systemPrompt)
 * @method int getMaxHistoryMessages()
 * @method void setMaxHistoryMessages(int $maxHistoryMessages)
 * @method int getRequestTimeout()
 * @method void setRequestTimeout(int $requestTimeout)
 * @method bool getIsDefault()
 * @method void setIsDefault(bool $isDefault)
 * @method bool getActive()
 * @method void setActive(bool $active)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class LlmConfig extends Entity implements JsonSerializable {
    protected string $userId = '';
    protected string $name = '';
    protected string $apiUrl = '';
    protected ?string $apiTokenEncrypted = null;
    protected string $modelName = '';
    protected float $temperature = 0.7;
    protected int $maxTokens = 2048;
    protected ?string $systemPrompt = null;
    protected int $maxHistoryMessages = 50;
    protected int $requestTimeout = 120000;
    protected ?bool $isDefault = false;  // Nullable boolean
    protected ?bool $active = true;      // Nullable boolean
    protected int $createdAt = 0;
    protected int $updatedAt = 0;

    public function __construct() {
        $this->addType('userId', 'string');
        $this->addType('name', 'string');
        $this->addType('apiUrl', 'string');
        $this->addType('apiTokenEncrypted', 'string');
        $this->addType('modelName', 'string');
        $this->addType('temperature', 'float');
        $this->addType('maxTokens', 'integer');
        $this->addType('systemPrompt', 'string');
        $this->addType('maxHistoryMessages', 'integer');
        $this->addType('requestTimeout', 'integer');
        $this->addType('isDefault', 'boolean');
        $this->addType('active', 'boolean');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'name' => $this->name,
            'apiUrl' => $this->apiUrl,
            'modelName' => $this->modelName,
            'temperature' => $this->temperature,
            'maxTokens' => $this->maxTokens,
            'systemPrompt' => $this->systemPrompt,
            'maxHistoryMessages' => $this->maxHistoryMessages,
            'requestTimeout' => $this->requestTimeout,
            'isDefault' => $this->isDefault,
            'active' => $this->active,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            // Note: We intentionally do not expose apiTokenEncrypted
        ];
    }
}
