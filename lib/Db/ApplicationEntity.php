<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Db;

use OCP\AppFramework\Db\Entity;
use DateTime;

/**
 * ApplicationEntity - Mitgliedsanträge und Abschlussrechnungen
 * 
 * Database columns (oc_antraege_application):
 * - id, user_id, member_id, status, title, type, data_json
 * - created_at, approved_at, invoice_id, invoice_number, invoice_amount
 */
class ApplicationEntity extends Entity {
    protected ?int $userId = null;
    protected ?int $memberId = null;
    protected string $status = 'pending';
    protected ?string $title = null;
    protected ?string $type = null;
    protected ?DateTime $createdAt = null;
    protected ?DateTime $approvedAt = null;
    protected ?string $dataJson = null;
    
    // Invoice-related fields for finance integration
    protected ?int $invoiceId = null;
    protected ?string $invoiceNumber = null;
    protected ?float $invoiceAmount = null;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('userId', 'integer');
        $this->addType('memberId', 'integer');
        $this->addType('status', 'string');
        $this->addType('title', 'string');
        $this->addType('type', 'string');
        $this->addType('createdAt', 'datetime');
        $this->addType('approvedAt', 'datetime');
        $this->addType('dataJson', 'string');
        $this->addType('invoiceId', 'integer');
        $this->addType('invoiceNumber', 'string');
        $this->addType('invoiceAmount', 'float');
    }

    // User ID
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): void { $this->userId = $userId; }

    // Member ID (link to clubsuite-core)
    public function getMemberId(): ?int { return $this->memberId; }
    public function setMemberId(?int $memberId): void { $this->memberId = $memberId; }

    // Status
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    // Title
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): void { $this->title = $title; }

    // Type
    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): void { $this->type = $type; }

    // Created At
    public function getCreatedAt(): ?DateTime { return $this->createdAt; }
    public function setCreatedAt(?DateTime $createdAt): void { $this->createdAt = $createdAt; }

    // Approved At
    public function getApprovedAt(): ?DateTime { return $this->approvedAt; }
    public function setApprovedAt(?DateTime $approvedAt): void { $this->approvedAt = $approvedAt; }

    // Data JSON
    public function getDataJson(): ?string { return $this->dataJson; }
    public function setDataJson(?string $dataJson): void { $this->dataJson = $dataJson; }
    
    /**
     * Get parsed data from JSON
     */
    public function getData(): array {
        if ($this->dataJson === null) {
            return [];
        }
        $data = json_decode($this->dataJson, true);
        return is_array($data) ? $data : [];
    }

    // Invoice ID (link to clubsuite-finance transaction)
    public function getInvoiceId(): ?int { return $this->invoiceId; }
    public function setInvoiceId(?int $invoiceId): void { $this->invoiceId = $invoiceId; }

    // Invoice Number
    public function getInvoiceNumber(): ?string { return $this->invoiceNumber; }
    public function setInvoiceNumber(?string $invoiceNumber): void { $this->invoiceNumber = $invoiceNumber; }

    // Invoice Amount
    public function getInvoiceAmount(): ?float { return $this->invoiceAmount; }
    public function setInvoiceAmount(?float $invoiceAmount): void { $this->invoiceAmount = $invoiceAmount; }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'member_id' => $this->memberId,
            'status' => $this->status,
            'title' => $this->title,
            'type' => $this->type,
            'created_at' => $this->createdAt?->format('c'),
            'approved_at' => $this->approvedAt?->format('c'),
            'data_json' => $this->dataJson,
            'invoice_id' => $this->invoiceId,
            'invoice_number' => $this->invoiceNumber,
            'invoice_amount' => $this->invoiceAmount,
        ];
    }
}
