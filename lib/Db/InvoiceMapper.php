<?php
namespace OCA\ClubSuiteApplications\Db;

use OCP\IDBConnection;

class InvoiceMapper {
    private IDBConnection $db;
    private string $table = 'antraege_invoice';
    public function __construct(IDBConnection $db) { $this->db = $db; }

    public function findByApplication(int $applicationId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->table)->where($qb->expr()->eq('application_id', $qb->createNamedParameter($applicationId)));
        $rows = $qb->executeQuery()->fetchAllAssociative();
        $out = [];
        foreach ($rows as $r) { $out[] = new InvoiceEntity($r); }
        return $out;
    }

    public function create(array $data): InvoiceEntity {
        $this->db->insert($this->table, $data);
        $data['id'] = (int)$this->db->lastInsertId($this->table);
        return new InvoiceEntity($data);
    }
}
