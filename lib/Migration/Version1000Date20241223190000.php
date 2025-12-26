<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20241223190000 extends SimpleMigrationStep {
    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Drop existing tables if they exist (to fix buggy schema)
        if ($schema->hasTable('localllm_messages')) {
            $schema->dropTable('localllm_messages');
        }
        if ($schema->hasTable('localllm_conversations')) {
            $schema->dropTable('localllm_conversations');
        }
        if ($schema->hasTable('localllm_configs')) {
            $schema->dropTable('localllm_configs');
        }

        // Create configs table
        if (!$schema->hasTable('localllm_configs')) {
            $table = $schema->createTable('localllm_configs');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 255,
            ]);
            $table->addColumn('api_url', Types::STRING, [
                'notnull' => true,
                'length' => 512,
            ]);
            $table->addColumn('api_token_encrypted', Types::TEXT, [
                'notnull' => false,
            ]);
            $table->addColumn('model_name', Types::STRING, [
                'notnull' => true,
                'length' => 255,
            ]);
            $table->addColumn('temperature', Types::DECIMAL, [
                'notnull' => true,
                'precision' => 3,
                'scale' => 2,
                'default' => '0.7',
            ]);
            $table->addColumn('max_tokens', Types::INTEGER, [
                'notnull' => true,
                'default' => 2048,
            ]);
            $table->addColumn('system_prompt', Types::TEXT, [
                'notnull' => false,
            ]);
            $table->addColumn('max_history_messages', Types::INTEGER, [
                'notnull' => true,
                'default' => 50,
            ]);
            $table->addColumn('request_timeout', Types::INTEGER, [
                'notnull' => true,
                'default' => 120000,
            ]);
            $table->addColumn('is_default', Types::BOOLEAN, [
                'notnull' => false,
                'default' => false,
            ]);
            $table->addColumn('active', Types::BOOLEAN, [
                'notnull' => false,
                'default' => true,
            ]);
            $table->addColumn('created_at', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::BIGINT, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'llm_cfg_user_idx');
            $table->addIndex(['user_id', 'is_default'], 'llm_cfg_user_def_idx');
        }

        // Create conversations table
        if (!$schema->hasTable('localllm_conversations')) {
            $table = $schema->createTable('localllm_conversations');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('config_id', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 255,
                'default' => 'New Conversation',
            ]);
            $table->addColumn('active', Types::BOOLEAN, [
                'notnull' => false,
                'default' => true,
            ]);
            $table->addColumn('created_at', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::BIGINT, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'llm_conv_user_idx');
            $table->addIndex(['user_id', 'active'], 'llm_conv_user_act_idx');
            $table->addIndex(['config_id'], 'llm_conv_cfg_idx');
        }

        // Create messages table
        if (!$schema->hasTable('localllm_messages')) {
            $table = $schema->createTable('localllm_messages');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('conversation_id', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('role', Types::STRING, [
                'notnull' => true,
                'length' => 32,
            ]);
            $table->addColumn('content', Types::TEXT, [
                'notnull' => true,
            ]);
            $table->addColumn('tokens_used', Types::INTEGER, [
                'notnull' => false,
            ]);
            $table->addColumn('created_at', Types::BIGINT, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['conversation_id'], 'llm_msg_conv_idx');
            $table->addIndex(['conversation_id', 'created_at'], 'llm_msg_conv_time_idx');
        }

        return $schema;
    }
}
