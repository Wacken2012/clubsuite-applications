<?php
namespace OCA\ClubSuiteApplications\Service;

/**
 * PdfService stub. In production integrate a PDF library (TCPDF, Dompdf, etc.)
 */
class PdfService {
    public function generateInvoicePdf(array $invoiceData): string {
        // return path or raw PDF content in production
        return 'PDF-BINARY-PLACEHOLDER';
    }
}
