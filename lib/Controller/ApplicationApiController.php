<?php
/**
 * © 2026 Stefan Schulz – Alle Rechte vorbehalten.
 */
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\ClubSuiteApplications\Service\ApplicationService;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\Db\DoesNotExistException;

class ApplicationApiController extends Controller {
    private ApplicationService $service;
    private LoggerInterface $logger;
    private ?string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        ApplicationService $service,
        LoggerInterface $logger,
        ?string $userId
    ) {
        parent::__construct($appName, $request);
        $this->service = $service;
        $this->logger = $logger;
        $this->userId = $userId;
    }

    /**
     * Get integration status (core and finance availability)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function integrationStatus(): JSONResponse {
        return new JSONResponse($this->service->getIntegrationStatus(), 200);
    }

    /**
     * List all members from clubsuite-core (for dropdown selection)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listMembers(): JSONResponse {
        try {
            $members = $this->service->listMembers();
            return new JSONResponse(['members' => $members], 200);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching members: ' . $e->getMessage());
            return new JSONResponse(['error' => 'Failed to fetch members'], 500);
        }
    }

    /**
     * List all applications (paginated)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): JSONResponse {
        try {
            $limit = (int)$this->request->getParam('limit', 25);
            $offset = (int)$this->request->getParam('offset', 0);
            $sort = $this->request->getParam('sort', 'created_at');
            $order = $this->request->getParam('order', 'DESC');

            if ($limit < 1 || $limit > 100) {
                $limit = 25;
            }
            if ($offset < 0) {
                $offset = 0;
            }

            $result = $this->service->listPaginated($limit, $offset, $sort, $order);
            
            return new JSONResponse([
                'total' => $result['total'],
                'limit' => $limit,
                'offset' => $offset,
                'data' => array_map(fn($item) => $item->jsonSerialize(), $result['items'])
            ], 200);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching applications: ' . $e->getMessage(), [
                'exception' => $e,
                'app' => 'clubsuite-applications'
            ]);
            
            return new JSONResponse([
                'error' => 'Failed to fetch applications',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List applications paginated (legacy endpoint)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listApplicationsPaginated(): JSONResponse {
        try {
            $limit = (int)$this->request->getParam('limit', 25);
            $offset = (int)$this->request->getParam('offset', 0);
            $sort = $this->request->getParam('sort', 'created_at');
            $order = $this->request->getParam('order', 'DESC');
            $status = $this->request->getParam('status');
            $type = $this->request->getParam('type');

            if ($limit < 1 || $limit > 100) {
                $limit = 25;
            }
            if ($offset < 0) {
                $offset = 0;
            }

            $result = $this->service->listPaginated($limit, $offset, $sort, $order, $status, $type);
            
            $rows = array_map(fn($item) => $item->jsonSerialize(), $result['items']);
            
            return new JSONResponse([
                'total' => $result['total'],
                'limit' => $limit,
                'offset' => $offset,
                'rows' => $rows
            ], 200);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching paginated applications: ' . $e->getMessage(), [
                'exception' => $e,
                'app' => 'clubsuite-applications'
            ]);
            
            return new JSONResponse([
                'error' => 'Failed to fetch applications',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single application by ID (with optional member data)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function show(int $id): JSONResponse {
        try {
            $includeMember = $this->request->getParam('includeMember', 'true') === 'true';
            
            if ($includeMember) {
                $data = $this->service->findWithMember($id);
            } else {
                $application = $this->service->find($id);
                $data = $application->jsonSerialize();
            }
            
            return new JSONResponse($data, 200);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Application not found'], 404);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching application: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            return new JSONResponse(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Create new application
     * 
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function create(): JSONResponse {
        try {
            if (!$this->userId) {
                return new JSONResponse(['error' => 'Not authenticated'], 401);
            }
            
            $data = $this->request->getParams();
            
            // Validate required fields
            if (empty($data['title'])) {
                return new JSONResponse(['error' => 'Title is required'], 400);
            }

            $application = $this->service->create($data, $this->userId);
            return new JSONResponse($application->jsonSerialize(), 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating application: ' . $e->getMessage(), [
                'exception' => $e,
                'userId' => $this->userId
            ]);
            return new JSONResponse(['error' => 'Failed to create application'], 500);
        }
    }

    /**
     * Update existing application
     * 
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function update(int $id): JSONResponse {
        try {
            $data = $this->request->getParams();
            $application = $this->service->update($id, $data);
            return new JSONResponse($application->jsonSerialize(), 200);
            
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Application not found'], 404);
        } catch (\Exception $e) {
            $this->logger->error('Error updating application: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            return new JSONResponse(['error' => 'Failed to update application'], 500);
        }
    }

    /**
     * Delete application
     * 
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function destroy(int $id): JSONResponse {
        try {
            $this->service->delete($id);
            return new JSONResponse(['success' => true], 200);
            
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Application not found'], 404);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting application: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            return new JSONResponse(['error' => 'Failed to delete application'], 500);
        }
    }

    /**
     * Approve application (and optionally create member)
     * 
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function approve(int $id): JSONResponse {
        try {
            $createMember = $this->request->getParam('createMember', 'true') === 'true';
            
            $application = $this->service->approve($id, $createMember);
            
            // Return with member data if available
            $data = $this->service->findWithMember($id);
            
            return new JSONResponse($data, 200);
            
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Application not found'], 404);
        } catch (\Exception $e) {
            $this->logger->error('Error approving application: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            return new JSONResponse(['error' => 'Failed to approve application'], 500);
        }
    }

    /**
     * Reject application
     * 
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function reject(int $id): JSONResponse {
        try {
            $application = $this->service->reject($id);
            return new JSONResponse($application->jsonSerialize(), 200);
            
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Application not found'], 404);
        } catch (\Exception $e) {
            $this->logger->error('Error rejecting application: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            return new JSONResponse(['error' => 'Failed to reject application'], 500);
        }
    }

    /**
     * Create invoice for an approved application
     * 
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function createInvoice(int $id): JSONResponse {
        try {
            $amount = (float)$this->request->getParam('amount');
            $invoiceNumber = $this->request->getParam('invoiceNumber');
            $date = $this->request->getParam('date', date('Y-m-d'));
            $createTransaction = $this->request->getParam('createTransaction', 'true') === 'true';
            
            if ($amount <= 0) {
                return new JSONResponse(['error' => 'Amount must be positive'], 400);
            }
            if (empty($invoiceNumber)) {
                return new JSONResponse(['error' => 'Invoice number is required'], 400);
            }
            
            $application = $this->service->createInvoice($id, $amount, $invoiceNumber, $date, $createTransaction);
            
            // Return with member data
            $data = $this->service->findWithMember($id);
            
            return new JSONResponse($data, 200);
            
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Application not found'], 404);
        } catch (\Exception $e) {
            $this->logger->error('Error creating invoice: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            return new JSONResponse(['error' => $e->getMessage()], 400);
        }
    }
}
