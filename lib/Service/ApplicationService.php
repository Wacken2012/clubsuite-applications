<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Service;

use OCA\ClubSuiteApplications\Db\ApplicationMapper;
use OCA\ClubSuiteApplications\Db\ApplicationEntity;
use OCA\ClubSuiteApplications\Events\ApplicationApprovedEvent;
use OCA\ClubSuiteApplications\Events\InvoiceCreatedEvent;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use DateTime;

class ApplicationService {
    private ApplicationMapper $mapper;
    private LoggerInterface $logger;
    private CoreIntegrationService $coreService;
    private FinanceIntegrationService $financeService;
    private IEventDispatcher $eventDispatcher;

    public function __construct(
        ApplicationMapper $mapper,
        LoggerInterface $logger,
        CoreIntegrationService $coreService,
        FinanceIntegrationService $financeService,
        IEventDispatcher $eventDispatcher
    ) {
        $this->mapper = $mapper;
        $this->logger = $logger;
        $this->coreService = $coreService;
        $this->financeService = $financeService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * List all applications
     * @return ApplicationEntity[]
     */
    public function listAll(): array {
        $this->logger->debug('ApplicationService::listAll called');
        return $this->mapper->findAll();
    }

    /**
     * List applications with pagination
     * @return array ['total'=>int, 'items'=>ApplicationEntity[]]
     */
    public function listPaginated(int $limit = 25, int $offset = 0, string $sort = 'created_at', string $order = 'DESC', ?string $status = null, ?string $type = null): array {
        $this->logger->debug('ApplicationService::listPaginated called', [
            'limit' => $limit,
            'offset' => $offset,
            'sort' => $sort,
            'order' => $order,
            'status' => $status,
            'type' => $type
        ]);

        $result = $this->mapper->findPaginated($limit, $offset, $sort, $order, $status, $type);
        
        $this->logger->debug('ApplicationService::listPaginated result', [
            'total' => $result['total'],
            'count' => count($result['items'])
        ]);

        return $result;
    }

    /**
     * Get application by ID
     * @throws DoesNotExistException
     */
    public function find(int $id): ApplicationEntity {
        $this->logger->debug('ApplicationService::find called', ['id' => $id]);
        return $this->mapper->find($id);
    }

    /**
     * Get application by ID with member data
     * @throws DoesNotExistException
     * @return array Application data with embedded member
     */
    public function findWithMember(int $id): array {
        $application = $this->find($id);
        $data = $application->jsonSerialize();
        
        // Fetch member data if memberId exists
        if ($application->getMemberId() !== null && $this->coreService->isAvailable()) {
            $member = $this->coreService->findMember($application->getMemberId());
            $data['member'] = $member;
        } else {
            $data['member'] = null;
        }
        
        return $data;
    }

    /**
     * Create new application
     */
    public function create(array $data, string $userId): ApplicationEntity {
        $this->logger->debug('ApplicationService::create called', ['userId' => $userId]);
        
        $entity = new ApplicationEntity();
        $entity->setUserId((int)$userId);
        $entity->setTitle($data['title'] ?? null);
        $entity->setType($data['type'] ?? null);
        $entity->setStatus($data['status'] ?? 'pending');
        $entity->setDataJson(isset($data['data']) ? json_encode($data['data']) : null);
        $entity->setCreatedAt(new DateTime());
        
        // Handle memberId - validate if provided
        if (!empty($data['memberId'])) {
            $memberId = (int)$data['memberId'];
            if ($this->coreService->isAvailable() && !$this->coreService->memberExists($memberId)) {
                $this->logger->warning('Invalid member ID provided', ['memberId' => $memberId]);
                // Still allow creation, but log warning
            }
            $entity->setMemberId($memberId);
        }
        
        return $this->mapper->insert($entity);
    }

    /**
     * Update existing application
     * @throws DoesNotExistException
     */
    public function update(int $id, array $data): ApplicationEntity {
        $this->logger->debug('ApplicationService::update called', ['id' => $id]);
        
        $entity = $this->mapper->find($id);
        
        if (isset($data['title'])) {
            $entity->setTitle($data['title']);
        }
        if (isset($data['type'])) {
            $entity->setType($data['type']);
        }
        if (isset($data['status'])) {
            $entity->setStatus($data['status']);
        }
        if (array_key_exists('memberId', $data)) {
            $memberId = $data['memberId'] !== null ? (int)$data['memberId'] : null;
            $entity->setMemberId($memberId);
        }
        if (isset($data['data'])) {
            $entity->setDataJson(json_encode($data['data']));
        }
        
        return $this->mapper->update($entity);
    }

    /**
     * Delete application
     * @throws DoesNotExistException
     */
    public function delete(int $id): ApplicationEntity {
        $this->logger->debug('ApplicationService::delete called', ['id' => $id]);
        $entity = $this->mapper->find($id);
        return $this->mapper->delete($entity);
    }

    /**
     * Approve application
     * Dispatches ApplicationApprovedEvent for listeners to create member, etc.
     * @throws DoesNotExistException
     */
    public function approve(int $id, bool $createMember = true): ApplicationEntity {
        $this->logger->debug('ApplicationService::approve called', ['id' => $id]);
        
        $entity = $this->mapper->find($id);
        $entity->setStatus('approved');
        $entity->setApprovedAt(new DateTime());
        $entity = $this->mapper->update($entity);
        
        // Dispatch event for listeners
        $event = new ApplicationApprovedEvent($entity, $createMember);
        $this->eventDispatcher->dispatchTyped($event);
        
        $this->logger->info('Application approved', [
            'app' => 'clubsuite-applications',
            'id' => $id,
            'createMember' => $createMember
        ]);
        
        // Re-fetch to get updated member_id if created
        return $this->mapper->find($id);
    }

    /**
     * Reject application
     * @throws DoesNotExistException
     */
    public function reject(int $id): ApplicationEntity {
        $this->logger->debug('ApplicationService::reject called', ['id' => $id]);
        $entity = $this->mapper->find($id);
        $entity->setStatus('rejected');
        return $this->mapper->update($entity);
    }

    /**
     * Create an invoice for an approved application
     * Dispatches InvoiceCreatedEvent for finance integration
     * 
     * @param int $id Application ID
     * @param float $amount Invoice amount
     * @param string $invoiceNumber Invoice number (e.g., "RE-1001")
     * @param string $date Invoice date (YYYY-MM-DD)
     * @param bool $createTransaction Whether to create a finance transaction
     * @throws DoesNotExistException
     * @throws \Exception if application is not approved
     */
    public function createInvoice(
        int $id,
        float $amount,
        string $invoiceNumber,
        string $date,
        bool $createTransaction = true
    ): ApplicationEntity {
        $this->logger->debug('ApplicationService::createInvoice called', [
            'id' => $id,
            'amount' => $amount,
            'invoiceNumber' => $invoiceNumber
        ]);
        
        $entity = $this->mapper->find($id);
        
        // Verify application is approved
        if ($entity->getStatus() !== 'approved') {
            throw new \Exception('Cannot create invoice for non-approved application');
        }
        
        // Update application with invoice data
        $entity->setInvoiceNumber($invoiceNumber);
        $entity->setInvoiceAmount($amount);
        $entity = $this->mapper->update($entity);
        
        // Dispatch event for finance integration
        $event = new InvoiceCreatedEvent($entity, $amount, $invoiceNumber, $date, $createTransaction);
        $this->eventDispatcher->dispatchTyped($event);
        
        $this->logger->info('Invoice created for application', [
            'app' => 'clubsuite-applications',
            'id' => $id,
            'invoiceNumber' => $invoiceNumber,
            'amount' => $amount
        ]);
        
        // Re-fetch to get updated invoice_id if transaction was created
        return $this->mapper->find($id);
    }

    /**
     * Get integration status
     */
    public function getIntegrationStatus(): array {
        return [
            'coreAvailable' => $this->coreService->isAvailable(),
            'financeAvailable' => $this->financeService->isAvailable(),
        ];
    }

    /**
     * Get member data for an application
     */
    public function getMemberData(int $memberId): ?array {
        if (!$this->coreService->isAvailable()) {
            return null;
        }
        return $this->coreService->findMember($memberId);
    }

    /**
     * List all members (for selection dropdowns)
     */
    public function listMembers(): array {
        if (!$this->coreService->isAvailable()) {
            return [];
        }
        return $this->coreService->listMembers();
    }
}
