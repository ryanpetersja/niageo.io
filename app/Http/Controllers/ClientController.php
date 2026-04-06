<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientStatementPdfService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private ClientStatementPdfService $statementPdf
    ) {}

    public function index(Request $request)
    {
        $query = Client::withCount('invoices', 'contacts');

        if ($search = $request->input('search')) {
            $query->where('company_name', 'like', "%{$search}%");
        }

        if ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->input('status') !== 'all') {
            $query->where('is_active', true);
        }

        $clients = $query->orderBy('company_name')->paginate(15)->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'billing_terms' => 'required|in:net_15,net_30,net_60,due_on_receipt',
            'billing_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $client = Client::create($validated);

        return redirect()->route('clients.show', $client)->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load(['contacts', 'pricingPresets.items', 'monitoredEndpoints', 'invoices' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'billing_terms' => 'required|in:net_15,net_30,net_60,due_on_receipt',
            'billing_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $client->update($validated);

        return redirect()->route('clients.show', $client)->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $name = $client->company_name;
        $client->delete();
        return redirect()->route('clients.index')->with('success', "Client \"{$name}\" and all related data deleted successfully.");
    }

    public function statement(Request $request, Client $client)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to = $request->input('to', now()->toDateString());

        return $this->statementPdf->stream($client, $from, $to);
    }

    public function statementDownload(Request $request, Client $client)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to = $request->input('to', now()->toDateString());

        return $this->statementPdf->download($client, $from, $to);
    }
}
