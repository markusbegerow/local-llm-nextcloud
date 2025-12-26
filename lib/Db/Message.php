<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getConversationId()
 * @method void setConversationId(int $conversationId)
 * @method string getRole()
 * @method void setRole(string $role)
 * @method string getContent()
 * @method void setContent(string $content)
 * @method int|null getTokensUsed()
 * @method void setTokensUsed(?int $tokensUsed)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class Message extends Entity implements JsonSerializable {
    protected int $conversationId = 0;
    protected string $role = '';
    protected string $content = '';
    protected ?int $tokensUsed = null;
    protected int $createdAt = 0;

    public function __construct() {
        $this->addType('conversationId', 'integer');
        $this->addType('role', 'string');
        $this->addType('content', 'string');
        $this->addType('tokensUsed', 'integer');
        $this->addType('createdAt', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversationId,
            'role' => $this->role,
            'content' => $this->content,
            'tokensUsed' => $this->tokensUsed,
            'createdAt' => $this->createdAt,
        ];
    }
}
