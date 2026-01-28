<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Listener;

use OCA\ClubSuiteApplications\Events\InvoiceCreatedEvent;
use OCA\ClubSuiteApplications\Service\FinanceIntegrationService;
use OCA\ClubSuiteApplications\Db\ApplicationMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Listener that creates a finance transaction when an invoice is created.
 * 
 * @implements IEventListener<InvoiceCreatedEvent>
 */
class CreateTransactionFromInvoiceListener implements IEventListener {
    private FinanceIntegrationService $financeService;
    private ApplicationMapper $mapper;
    private LoggerInterface $logger;

    public function __construct(
        FinanceIntegrationService $financeService,
        ApplicationMapper $mapper,
        LoggerInterface $logger
    ) {
        $this->financeService = $financeService;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    public function handle(Event $event): void {
        if (!($event instanceof InvoiceCreatedEvent)) {
            return;
        }

        // Skip if transaction creation is disabled
        if (!$event->shouldCreateTransaction()) {
            $this->logger->debug('Transaction creation disabled for this event');
            return;
        }

        // Check if finance integration is available
        if (!$this->financeService->isAvailable()) {
            $this->logger->info('clubsuite-finance not available, skipping transaction creation');
            return;
        }

        $application = $event->getApplication();

        // Create the finance transaction
        $transactionId = $this->financeService->createTransactionFromInvoice(
            $application,
            $event->getAmount(),
            $event->getInvoiceNumber(),
            $event->getInvoiceDate()
        );

        if ($transactionId !== null) {
            // Update the application with invoice data
            $application->setInvoiceId($transactionId);
            $application->setInvoiceNumber($event->getInvoiceNumber());
            $application->setInvoiceAmount($event->getAmount());
            $this->mapper->update($application);

            $this->logger->info('Transaction created and linked to application', [
                'app' => 'clubsuite-applications',
                'applicationId' => $application->getId(),
                'transactionId' => $transactionId,
                'invoiceNumber' => $event->getInvoiceNumber()
            ]);
        }
    }
}
