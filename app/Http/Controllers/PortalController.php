<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use App\Services\Invoicing\InvoicePdfService;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        $user = auth()->user();

        $projects = Project::where('client_id', $user->id)
            ->with('entity')
            ->latest()
            ->limit(5)
            ->get();

        $invoices = Invoice::whereHas('client', fn($q) => $q->where('id', $user->id))
            ->with(['entity', 'payment'])
            ->latest('issue_date')
            ->limit(5)
            ->get();

        return view('portal.dashboard', compact('projects', 'invoices'));
    }

    public function invoices()
    {
        $invoices = Invoice::whereHas('client', fn($q) => $q->where('id', auth()->id()))
            ->with(['entity', 'payment', 'project'])
            ->latest('issue_date')
            ->paginate(20);

        return view('portal.invoices.index', compact('invoices'));
    }

    public function showInvoice(Invoice $invoice)
    {
        // Authorization check
        if ($invoice->client_id !== auth()->id()) {
            abort(403);
        }

        $invoice->load(['entity', 'payment', 'project', 'client']);

        return view('portal.invoices.show', compact('invoice'));
    }

    public function downloadInvoice(Invoice $invoice)
    {
        // Authorization check
        if ($invoice->client_id !== auth()->id()) {
            abort(403);
        }

        $pdfService = app(InvoicePdfService::class);

        return $pdfService->downloadPdf($invoice);
    }
}
