<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add member_id, type, and approved_at columns to applications table
 */
class Version20260107_AddFields extends SimpleMigrationStep {
    
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('oc_antraege_application')) {
            $table = $schema->getTable('oc_antraege_application');
            
            // Add member_id column if not exists
            if (!$table->hasColumn('member_id')) {
                $table->addColumn('member_id', 'string', [
                    'notnull' => false,
                    'length' => 100,
                ]);
                $table->addIndex(['member_id'], 'antraege_app_member_id');
            }
            
            // Add type column if not exists
            if (!$table->hasColumn('type')) {
                $table->addColumn('type', 'string', [
                    'notnull' => false,
                    'length' => 50,
                ]);
                $table->addIndex(['type'], 'antraege_app_type');
            }
            
            // Add approved_at column if not exists
            if (!$table->hasColumn('approved_at')) {
                $table->addColumn('approved_at', 'datetime', [
                    'notnull' => false,
                ]);
            }
        }

        return $schema;
    }
}
