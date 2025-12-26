<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getConfigId()
 * @method void setConfigId(int $configId)
 * @method string getName()
 * @method void setName(string $name)
 * @method bool getActive()
 * @method void setActive(bool $active)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class Conversation extends Entity implements JsonSerializable {
    protected string $userId = '';
    protected int $configId = 0;
    protected string $name = 'New Conversation';
    protected ?bool $active = true;  // Nullable boolean
    protected int $createdAt = 0;
    protected int $updatedAt = 0;

    public function __construct() {
        $this->addType('userId', 'string');
        $this->addType('configId', 'integer');
        $this->addType('name', 'string');
        $this->addType('active', 'boolean');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'configId' => $this->configId,
            'name' => $this->name,
            'active' => $this->active,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
