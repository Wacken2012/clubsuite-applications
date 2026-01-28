<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * @extends QBMapper<ApplicationEntity>
 */
class ApplicationMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'antraege_application', ApplicationEntity::class);
    }

    /**
     * Find all applications
     * @return ApplicationEntity[]
     */
    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->orderBy('created_at', 'DESC');
        return $this->findEntities($qb);
    }

    /**
     * Find applications with pagination
     * @return array ['total'=>int, 'items'=>ApplicationEntity[]]
     */
    public function findPaginated(int $limit = 25, int $offset = 0, string $sort = 'created_at', string $order = 'DESC', ?string $status = null, ?string $type = null): array {
        // Validate sort field
        $allowedSort = ['id', 'created_at', 'status', 'title', 'type'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }
        
        // Validate order
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // Get total count with filters
        $qbCount = $this->db->getQueryBuilder();
        $qbCount->select($qbCount->func()->count('id'))
                ->from($this->getTableName());
        
        if ($status !== null && $status !== '') {
            $qbCount->andWhere($qbCount->expr()->eq('status', $qbCount->createNamedParameter($status)));
        }
        if ($type !== null && $type !== '') {
            $qbCount->andWhere($qbCount->expr()->eq('type', $qbCount->createNamedParameter($type)));
        }
        
        $total = (int)$qbCount->executeQuery()->fetchOne();

        // Get paginated results
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName());
        
        if ($status !== null && $status !== '') {
            $qb->andWhere($qb->expr()->eq('status', $qb->createNamedParameter($status)));
        }
        if ($type !== null && $type !== '') {
            $qb->andWhere($qb->expr()->eq('type', $qb->createNamedParameter($type)));
        }
        
        $qb->orderBy($sort, $order)
           ->setFirstResult($offset)
           ->setMaxResults($limit);
        
        $items = $this->findEntities($qb);
        
        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * Find application by ID
     * @throws DoesNotExistException
     */
    public function find(int $id): ApplicationEntity {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        return $this->findEntity($qb);
    }
}
