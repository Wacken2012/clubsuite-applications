<?php
/**
 * © 2026 Stefan Schulz – Alle Rechte vorbehalten.
 */
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add invoice and finance integration fields to applications table
 * - invoice_id: Reference to finance transaction
 * - invoice_number: Human-readable invoice number
 * - invoice_amount: Amount in cents (integer for precision)
 * - member_id changed from string to integer for FK reference
 */
class Version20260108_AddInvoiceFields extends SimpleMigrationStep {
    
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('oc_antraege_application')) {
            $table = $schema->getTable('oc_antraege_application');
            
            // Add invoice_id column if not exists (reference to finance transaction)
            if (!$table->hasColumn('invoice_id')) {
                $table->addColumn('invoice_id', Types::INTEGER, [
                    'notnull' => false,
                    'default' => null,
                ]);
            }
            
            // Add invoice_number column if not exists
            if (!$table->hasColumn('invoice_number')) {
                $table->addColumn('invoice_number', Types::STRING, [
                    'notnull' => false,
                    'length' => 50,
                    'default' => null,
                ]);
            }
            
            // Add invoice_amount column if not exists (stored in cents)
            if (!$table->hasColumn('invoice_amount')) {
                $table->addColumn('invoice_amount', Types::INTEGER, [
                    'notnull' => false,
                    'default' => null,
                ]);
            }
            
            // Add index on invoice_id for lookups
            if (!$table->hasIndex('antraege_app_invoice_id')) {
                $table->addIndex(['invoice_id'], 'antraege_app_invoice_id');
            }
            
            // Add index on invoice_number for lookups
            if (!$table->hasIndex('antraege_app_invoice_number')) {
                $table->addIndex(['invoice_number'], 'antraege_app_invoice_number');
            }
        }

        return $schema;
    }
}
