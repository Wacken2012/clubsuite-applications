<?php
namespace OCA\ClubSuiteApplications\Controller;

use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCA\ClubSuiteApplications\Service\InvoiceService;
use OCA\ClubSuiteApplications\Service\PdfService;

class InvoiceController extends OCSController {
    private InvoiceService $service;
    private PdfService $pdf;

    public function __construct(string $appName, IRequest $request, InvoiceService $service, PdfService $pdf) { parent::__construct($appName, $request); $this->service = $service; $this->pdf = $pdf; }

    public function listInvoices(int $applicationId): JSONResponse { return new JSONResponse($this->service->listByApplication($applicationId)); }

    public function createInvoice(array $data): JSONResponse {
        try { $inv = $this->service->createInvoice($data); $pdf = $this->pdf->generateInvoicePdf((array)$inv); return new JSONResponse(['status' => 'success', 'invoice' => $inv, 'pdf' => $pdf]); }
        catch (\Throwable $e) { return new JSONResponse(['status' => 'error', 'message' => $e->getMessage()], 400); }
    }
}
