<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version20260104 extends SimpleMigrationStep {
    
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('oc_antraege_application')) {
            $table = $schema->createTable('oc_antraege_application');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('title', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('data_json', 'text', [
                'notnull' => false,
            ]);
            $table->addColumn('status', 'string', [
                'notnull' => true,
                'length' => 50,
                'default' => 'pending',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'antraege_app_user_id');
            $table->addIndex(['status'], 'antraege_app_status');
            $table->addIndex(['created_at'], 'antraege_app_created_at');
        }

        if (!$schema->hasTable('oc_antraege_invoice')) {
            $table = $schema->createTable('oc_antraege_invoice');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('application_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('amount', 'decimal', [
                'notnull' => true,
                'precision' => 10,
                'scale' => 2,
            ]);
            $table->addColumn('description', 'text', [
                'notnull' => false,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['application_id'], 'antraege_inv_app_id');
        }

        return $schema;
    }
}
