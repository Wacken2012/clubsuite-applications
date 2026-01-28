<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Service;

use OCA\ClubSuiteApplications\Db\ApplicationEntity;
use Psr\Log\LoggerInterface;

/**
 * Integration service for clubsuite-finance (Transaction management)
 * 
 * Uses cross-app service calls via Nextcloud's DI container.
 * Falls back gracefully if clubsuite-finance is not installed.
 */
class FinanceIntegrationService {
    private LoggerInterface $logger;
    private bool $financeAvailable;
    private $transactionService = null;
    
    // Default account ID for membership fees (can be configured)
    private const DEFAULT_ACCOUNT_ID = 1;
    private const DEFAULT_CATEGORY_ID = 1; // "Mitgliedsbeiträge" category

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->financeAvailable = $this->checkFinanceAvailable();
    }

    /**
     * Check if clubsuite-finance app is available
     */
    private function checkFinanceAvailable(): bool {
        try {
            return class_exists('\OCA\ClubSuiteFinance\Service\TransactionService');
        } catch (\Throwable $e) {
            $this->logger->debug('clubsuite-finance not available: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the TransactionService from clubsuite-finance
     */
    private function getTransactionService(): ?object {
        if (!$this->financeAvailable) {
            return null;
        }
        
        if ($this->transactionService === null) {
            try {
                $app = new \OCA\ClubSuiteFinance\AppInfo\Application();
                $this->transactionService = $app->getContainer()->get(\OCA\ClubSuiteFinance\Service\TransactionService::class);
            } catch (\Throwable $e) {
                $this->logger->warning('Could not get TransactionService: ' . $e->getMessage());
                return null;
            }
        }
        
        return $this->transactionService;
    }

    /**
     * Check if finance integration is available
     */
    public function isAvailable(): bool {
        return $this->financeAvailable && $this->getTransactionService() !== null;
    }

    /**
     * Create a finance transaction for an invoice
     * 
     * @param ApplicationEntity $application The application
     * @param float $amount The invoice amount
     * @param string $invoiceNumber The invoice number (e.g., "RE-1001")
     * @param string $date The invoice date (YYYY-MM-DD format)
     * @param int|null $accountId Optional account ID (defaults to membership fees account)
     * @param int|null $categoryId Optional category ID
     * @return int|null The transaction ID, or null on failure
     */
    public function createTransactionFromInvoice(
        ApplicationEntity $application,
        float $amount,
        string $invoiceNumber,
        string $date,
        ?int $accountId = null,
        ?int $categoryId = null
    ): ?int {
        $service = $this->getTransactionService();
        if ($service === null) {
            $this->logger->warning('Cannot create transaction: clubsuite-finance not available');
            return null;
        }

        try {
            $purpose = sprintf(
                'Rechnung %s - %s (Antrag #%d)',
                $invoiceNumber,
                $application->getTitle() ?? 'Mitgliedsantrag',
                $application->getId()
            );

            $this->logger->info('Creating finance transaction for invoice', [
                'app' => 'clubsuite-applications',
                'invoiceNumber' => $invoiceNumber,
                'amount' => $amount,
                'applicationId' => $application->getId()
            ]);

            $transaction = $service->createTransaction(
                $accountId ?? self::DEFAULT_ACCOUNT_ID,
                $amount,
                $date,
                $categoryId ?? self::DEFAULT_CATEGORY_ID,
                $application->getMemberId(),
                $purpose
            );

            $this->logger->info('Transaction created with ID: ' . $transaction->getId(), [
                'app' => 'clubsuite-applications',
                'transactionId' => $transaction->getId()
            ]);

            return $transaction->getId();
            
        } catch (\Throwable $e) {
            $this->logger->error('Error creating transaction from invoice: ' . $e->getMessage(), [
                'app' => 'clubsuite-applications',
                'invoiceNumber' => $invoiceNumber,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Get transactions for a specific member
     * 
     * @param int $memberId
     * @return array
     */
    public function getTransactionsByMember(int $memberId): array {
        $service = $this->getTransactionService();
        if ($service === null) {
            return [];
        }

        try {
            $transactions = $service->listByMember($memberId);
            return array_map(fn($t) => $t->jsonSerialize(), $transactions);
        } catch (\Throwable $e) {
            $this->logger->warning('Error fetching transactions for member: ' . $e->getMessage());
            return [];
        }
    }
}
