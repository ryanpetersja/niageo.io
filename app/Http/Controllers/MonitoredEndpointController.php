<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\MonitoredEndpoint;
use App\Services\UptimeService;
use Illuminate\Http\Request;

class MonitoredEndpointController extends Controller
{
    public function __construct(
        private UptimeService $uptimeService
    ) {}

    public function index(Request $request)
    {
        $query = MonitoredEndpoint::with('client');

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        $endpoints = $query->orderByRaw("FIELD(current_status, 'down', 'degraded', 'up')")
            ->orderBy('name')
            ->get();

        $summary = $this->uptimeService->getSummary();
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('uptime.index', compact('endpoints', 'summary', 'clients'));
    }

    public function create(Request $request)
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $selectedClientId = $request->input('client_id');

        return view('uptime.create', compact('clients', 'selectedClientId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'check_interval_minutes' => 'required|integer|min:1|max:1440',
            'timeout_seconds' => 'required|integer|min:1|max:60',
            'degraded_threshold_ms' => 'required|integer|min:100|max:30000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $endpoint = MonitoredEndpoint::create($validated);

        return redirect()->route('uptime.show', $endpoint)->with('success', 'Endpoint added successfully.');
    }

    public function show(MonitoredEndpoint $uptime)
    {
        $uptime->load('client');
        $checks = $uptime->uptimeChecks()
            ->orderBy('checked_at', 'desc')
            ->limit(100)
            ->get();

        return view('uptime.show', ['endpoint' => $uptime, 'checks' => $checks]);
    }

    public function edit(MonitoredEndpoint $uptime)
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('uptime.edit', ['endpoint' => $uptime, 'clients' => $clients]);
    }

    public function update(Request $request, MonitoredEndpoint $uptime)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'check_interval_minutes' => 'required|integer|min:1|max:1440',
            'timeout_seconds' => 'required|integer|min:1|max:60',
            'degraded_threshold_ms' => 'required|integer|min:100|max:30000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $uptime->update($validated);

        return redirect()->route('uptime.show', $uptime)->with('success', 'Endpoint updated successfully.');
    }

    public function destroy(MonitoredEndpoint $uptime)
    {
        $name = $uptime->name;
        $uptime->delete();

        return redirect()->route('uptime.index')->with('success', "Endpoint \"{$name}\" deleted successfully.");
    }

    public function check(MonitoredEndpoint $endpoint)
    {
        $check = $this->uptimeService->checkEndpoint($endpoint);

        $message = match ($check->status) {
            'up' => "Endpoint is UP ({$check->response_time_ms}ms)",
            'degraded' => "Endpoint is DEGRADED ({$check->response_time_ms}ms — above {$endpoint->degraded_threshold_ms}ms threshold)",
            'down' => "Endpoint is DOWN" . ($check->error_message ? ": {$check->error_message}" : ''),
        };

        return redirect()->back()->with('success', $message);
    }
}
