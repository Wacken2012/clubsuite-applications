<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version20260105_AddIndexes extends SimpleMigrationStep {
    
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('oc_antraege_application')) {
            $table = $schema->getTable('oc_antraege_application');
            
            if (!$table->hasIndex('idx_antraege_app_status')) {
                $table->addIndex(['status'], 'idx_antraege_app_status');
            }
            if (!$table->hasIndex('idx_antraege_app_created')) {
                $table->addIndex(['created_at'], 'idx_antraege_app_created');
            }
        }

        return $schema;
    }
}
