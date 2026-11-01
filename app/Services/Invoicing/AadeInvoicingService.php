<?php

namespace App\Services\Invoicing;

use App\Models\Invoice;
use App\Models\AadeLog;
use Firebed\AadeMyData\Http\MyDataRequest;
use Firebed\AadeMyData\Models\InvoicesDoc;
use Firebed\AadeMyData\Models\Invoice as AadeInvoice;
use Firebed\AadeMyData\Models\Issuer;
use Firebed\AadeMyData\Models\Counterpart;
use Firebed\AadeMyData\Models\InvoiceRow;
use Firebed\AadeMyData\Models\InvoiceHeader;
use Firebed\AadeMyData\Models\InvoiceSummary;
use Firebed\AadeMyData\Enums\InvoiceType;
use Illuminate\Support\Str;

class AadeInvoicingService
{
    protected MyDataRequest $request;

    public function __construct()
    {
        $this->request = new MyDataRequest(
            config('aade.user_id'),
            config('aade.subscription_key'),
            config('aade.environment', 'demo')
        );
    }

    public function transmitToAade(Invoice $invoice): void
    {
        $startTime = microtime(true);

        try {
            $aadeInvoice = $this->buildAadeInvoice($invoice);
            $invoicesDoc = new InvoicesDoc();
            $invoicesDoc->addInvoice($aadeInvoice);

            $response = $this->request->sendInvoices($invoicesDoc);

            if ($response->hasErrors()) {
                throw new \Exception('AADE validation errors: ' . json_encode($response->getErrors()));
            }

            $invoiceResponse = $response->getInvoice(0);

            $invoice->update([
                'uid' => $invoiceResponse->getUid(),
                'mark' => $invoiceResponse->getMark(),
                'qr_url' => $invoiceResponse->getQrUrl(),
                'transmitted_at' => now(),
                'status' => 'issued',
            ]);

            $this->logRequest($invoice, 'SendInvoices', $invoicesDoc->toArray(), $response->toArray(), 'success', null, $startTime);
        } catch (\Exception $e) {
            $this->logRequest($invoice, 'SendInvoices', [], null, 'error', [$e->getMessage()], $startTime);
            throw $e;
        }
    }

    protected function buildAadeInvoice(Invoice $invoice): AadeInvoice
    {
        $entity = $invoice->entity;
        $client = $invoice->client;

        $aadeInvoice = new AadeInvoice();

        // Issuer (Entity)
        $issuer = new Issuer();
        $issuer->setVatNumber($entity->vat_number);
        $issuer->setCountry('GR');
        $issuer->setBranch(0);

        // Counterpart (Client)
        $counterpart = new Counterpart();
        $counterpart->setVatNumber($client?->vat_number ?? '');
        $counterpart->setCountry('GR');
        $counterpart->setBranch(0);
        $counterpart->setName($client?->name ?? 'Individual');

        // Header
        $header = new InvoiceHeader();
        $header->setSeries($invoice->series_code);
        $header->setAa($invoice->sequence_number);
        $header->setIssueDate($invoice->issue_date);
        $header->setInvoiceType(InvoiceType::TYPE_2_1); // Services
        $header->setCurrency($invoice->currency);

        // Rows
        $rows = collect($invoice->line_items)->map(function ($item) {
            $row = new InvoiceRow();
            $row->setLineNumber(1);
            $row->setNetValue($item['unit_price']);
            $row->setVatCategory(1); // 24% VAT
            $row->setVatAmount($item['vat_amount']);
            return $row;
        });

        // Summary
        $summary = new InvoiceSummary();
        $summary->setTotalNetValue($invoice->subtotal);
        $summary->setTotalVatAmount($invoice->vat_amount);
        $summary->setTotalGrossValue($invoice->total);

        $aadeInvoice->setIssuer($issuer);
        $aadeInvoice->setCounterpart($counterpart);
        $aadeInvoice->setInvoiceHeader($header);
        foreach ($rows as $row) {
            $aadeInvoice->addInvoiceRow($row);
        }
        $aadeInvoice->setInvoiceSummary($summary);

        return $aadeInvoice;
    }

    public function cancelInvoice(Invoice $invoice): void
    {
        if (!$invoice->canBeCancelled()) {
            throw new \Exception('Invoice cannot be cancelled');
        }

        $startTime = microtime(true);

        try {
            $response = $this->request->cancelInvoice($invoice->mark);

            if ($response->hasErrors()) {
                throw new \Exception('AADE cancellation errors: ' . json_encode($response->getErrors()));
            }

            $invoice->update([
                'cancellation_mark' => $response->getCancellationMark(),
                'cancelled_at' => now(),
                'status' => 'cancelled',
            ]);

            $this->logRequest($invoice, 'CancelInvoice', ['mark' => $invoice->mark], $response->toArray(), 'success', null, $startTime);
        } catch (\Exception $e) {
            $this->logRequest($invoice, 'CancelInvoice', ['mark' => $invoice->mark], null, 'error', [$e->getMessage()], $startTime);
            throw $e;
        }
    }

    protected function logRequest(Invoice $invoice, string $operation, array $request, ?array $response, string $status, ?array $errors, float $startTime): void
    {
        $duration = (microtime(true) - $startTime) * 1000;

        AadeLog::create([
            'invoice_id' => $invoice->id,
            'operation' => $operation,
            'invoice_type' => $invoice->document_type,
            'mark' => $invoice->mark,
            'uid' => $invoice->uid,
            'request_payload' => $request,
            'response_payload' => $response,
            'status' => $status,
            'errors' => $errors,
            'duration_ms' => (int) $duration,
            'created_at' => now(),
        ]);
    }
}
