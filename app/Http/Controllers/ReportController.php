<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Report;
use App\Services\ReportMailService;
use App\Services\ReportPdfService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private ReportPdfService $pdfService,
        private ReportMailService $mailService,
    ) {}

    public function index(Request $request)
    {
        $query = Report::with('client');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('report_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('reports.index', compact('reports', 'clients'));
    }

    public function create(Request $request)
    {
        $clients = Client::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('repositories', fn ($sub) => $sub->where('is_active', true))
                  ->orWhereHas('servers', fn ($sub) => $sub->where('is_active', true));
            })
            ->orderBy('company_name')
            ->get();

        $selectedClientId = $request->input('client_id');

        return view('reports.create', compact('clients', 'selectedClientId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'invoice_id' => 'nullable|exists:invoices,id',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        $report = $this->reportService->create($validated);

        return redirect()->route('reports.show', $report)->with('success', 'Report created successfully.');
    }

    public function show(Report $report)
    {
        $report->load(['client.contacts', 'creator', 'invoice', 'statusHistory.changedBy']);
        $validTransitions = $this->reportService->getValidTransitions($report);

        return view('reports.show', compact('report', 'validTransitions'));
    }

    public function edit(Report $report)
    {
        if ($report->status !== 'draft') {
            return redirect()->route('reports.show', $report)->with('error', 'Only draft reports can be edited.');
        }

        $clients = Client::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('repositories', fn ($sub) => $sub->where('is_active', true))
                  ->orWhereHas('servers', fn ($sub) => $sub->where('is_active', true));
            })
            ->orderBy('company_name')
            ->get();

        return view('reports.edit', compact('report', 'clients'));
    }

    public function update(Request $request, Report $report)
    {
        if ($report->status !== 'draft') {
            return redirect()->route('reports.show', $report)->with('error', 'Only draft reports can be edited.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'invoice_id' => 'nullable|exists:invoices,id',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        $this->reportService->update($report, $validated);

        return redirect()->route('reports.show', $report)->with('success', 'Report updated successfully.');
    }

    public function destroy(Report $report)
    {
        try {
            $this->reportService->delete($report);
            return redirect()->route('reports.index')->with('success', 'Report deleted successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->route('reports.show', $report)->with('error', $e->getMessage());
        }
    }

    public function generate(Report $report)
    {
        try {
            $this->reportService->generate($report);
            return redirect()->route('reports.show', $report)->with('success', 'Report generated successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->route('reports.show', $report)->with('error', $e->getMessage());
        }
    }

    public function regenerate(Report $report)
    {
        try {
            $this->reportService->regenerate($report);
            return redirect()->route('reports.show', $report)->with('success', 'Report regenerated successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->route('reports.show', $report)->with('error', $e->getMessage());
        }
    }

    public function transition(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->reportService->transition($report, $validated['status'], $validated['notes'] ?? null);
            return redirect()->route('reports.show', $report)->with('success', "Report marked as {$validated['status']}.");
        } catch (\RuntimeException $e) {
            return redirect()->route('reports.show', $report)->with('error', $e->getMessage());
        }
    }

    public function send(Request $request, Report $report)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        if ($report->status !== 'generated') {
            return redirect()->route('reports.show', $report)->with('error', 'Only generated reports can be sent.');
        }

        try {
            $this->mailService->send($report, $validated['email']);
            $this->reportService->markAsSent($report, $validated['email']);
            return redirect()->route('reports.show', $report)->with('success', "Report sent to {$validated['email']}.");
        } catch (\Exception $e) {
            return redirect()->route('reports.show', $report)->with('error', 'Failed to send report: ' . $e->getMessage());
        }
    }

    public function linkInvoice(Request $request, Report $report)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $report->update(['invoice_id' => $validated['invoice_id']]);

        return redirect()->route('reports.show', $report)->with('success', 'Invoice linked successfully.');
    }

    public function unlinkInvoice(Report $report)
    {
        $report->update(['invoice_id' => null]);

        return redirect()->route('reports.show', $report)->with('success', 'Invoice unlinked.');
    }

    public function pdf(Report $report)
    {
        return $this->pdfService->stream($report);
    }

    public function downloadPdf(Report $report)
    {
        return $this->pdfService->download($report);
    }

    public function updateSummary(Request $request, Report $report)
    {
        if (in_array($report->status, ['sent', 'archived'])) {
            return response()->json(['message' => 'Cannot edit sent or archived reports.'], 422);
        }

        $validated = $request->validate([
            'ai_summary' => 'required|array',
            'ai_summary.features' => 'present|array',
            'ai_summary.bugs' => 'present|array',
            'ai_summary.improvements' => 'present|array',
            'ai_summary.security' => 'present|array',
            'ai_summary.infrastructure' => 'present|array',
        ]);

        $report->update(['ai_summary' => $validated['ai_summary']]);

        return response()->json(['success' => true]);
    }

    public function updateServerSummary(Request $request, Report $report)
    {
        if (in_array($report->status, ['sent', 'archived'])) {
            return response()->json(['message' => 'Cannot edit sent or archived reports.'], 422);
        }

        $validated = $request->validate([
            'server_summary' => 'required|array',
            'server_summary.features' => 'present|array',
            'server_summary.bugs' => 'present|array',
            'server_summary.improvements' => 'present|array',
            'server_summary.security' => 'present|array',
            'server_summary.infrastructure' => 'present|array',
        ]);

        $report->update(['server_summary' => $validated['server_summary']]);

        return response()->json(['success' => true]);
    }

    public function clientInvoices(Client $client)
    {
        $invoices = $client->invoices()
            ->select('id', 'invoice_number', 'status', 'total')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($invoices);
    }
}
