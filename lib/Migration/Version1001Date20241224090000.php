<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1001Date20241224090000 extends SimpleMigrationStep {
    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Drop and recreate conversations table to fix name column default
        if ($schema->hasTable('localllm_conversations')) {
            $schema->dropTable('localllm_conversations');
        }

        // Recreate conversations table with name default value
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

        return $schema;
    }
}
