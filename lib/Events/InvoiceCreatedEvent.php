<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Events;

use OCA\ClubSuiteApplications\Db\ApplicationEntity;
use OCP\EventDispatcher\Event;

/**
 * Event fired when an invoice is created for an application.
 * Listeners can use this to create finance transactions, send emails, etc.
 */
class InvoiceCreatedEvent extends Event {
    private ApplicationEntity $application;
    private float $amount;
    private string $invoiceNumber;
    private string $invoiceDate;
    private bool $createTransaction;

    public function __construct(
        ApplicationEntity $application,
        float $amount,
        string $invoiceNumber,
        string $invoiceDate,
        bool $createTransaction = true
    ) {
        parent::__construct();
        $this->application = $application;
        $this->amount = $amount;
        $this->invoiceNumber = $invoiceNumber;
        $this->invoiceDate = $invoiceDate;
        $this->createTransaction = $createTransaction;
    }

    public function getApplication(): ApplicationEntity {
        return $this->application;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getInvoiceNumber(): string {
        return $this->invoiceNumber;
    }

    public function getInvoiceDate(): string {
        return $this->invoiceDate;
    }

    public function shouldCreateTransaction(): bool {
        return $this->createTransaction;
    }
}
