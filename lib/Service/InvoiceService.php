<?php
namespace OCA\ClubSuiteApplications\Service;

use OCA\ClubSuiteApplications\Db\InvoiceMapper;

class InvoiceService {
    private InvoiceMapper $mapper;
    public function __construct(InvoiceMapper $mapper) { $this->mapper = $mapper; }

    public function listByApplication(int $applicationId): array { return $this->mapper->findByApplication($applicationId); }
    public function createInvoice(array $data) { return $this->mapper->create($data); }
}
