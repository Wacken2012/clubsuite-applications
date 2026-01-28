<?php
namespace OCA\ClubSuiteApplications\Controller;

use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCA\ClubSuiteApplications\Service\ApplicationService;

class ApplicationController extends OCSController {
    private ApplicationService $service;
    public function __construct(string $appName, IRequest $request, ApplicationService $service) { parent::__construct($appName, $request); $this->service = $service; }

    public function listApplications(): JSONResponse { return new JSONResponse($this->service->listApplications()); }

    public function listApplicationsPaginated(): JSONResponse {
        $limit = (int)$this->request->getParam('limit', 25);
        $offset = (int)$this->request->getParam('offset', 0);
        $sort = $this->request->getParam('sort', 'created_at');
        $order = $this->request->getParam('order', 'DESC');

        $service = new \OCA\ClubSuiteApplications\Service\ApplicationService($this->service->mapper ?? $this->service);
        $result = $service->listApplicationsPaginated($limit, $offset, $sort, $order);
        return new \OCP\AppFramework\Http\JSONResponse($result);
    }

    public function createApplication(array $data): JSONResponse {
        try { $a = $this->service->createApplication($data); return new JSONResponse(['status' => 'success', 'application' => $a]); }
        catch (\Throwable $e) { return new JSONResponse(['status' => 'error', 'message' => $e->getMessage()], 400); }
    }
}
