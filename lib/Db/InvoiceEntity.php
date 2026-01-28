<?php
namespace OCA\ClubSuiteApplications\Db;

class InvoiceEntity {
    private ?int $id = null;
    private int $applicationId;
    private float $amount;
    private ?string $description;
    private \DateTime $createdAt;

    public function __construct(array $data = []) { foreach ($data as $k => $v) { $this->$k = $v; } }
    public function getId(): ?int { return $this->id; }
    public function getApplicationId(): int { return $this->applicationId; }
    public function getAmount(): float { return $this->amount; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
}
