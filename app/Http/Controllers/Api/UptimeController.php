<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MonitoredEndpointResource;
use App\Models\MonitoredEndpoint;
use App\Services\UptimeService;
use Illuminate\Http\Request;

class UptimeController extends Controller
{
    public const STATUS_RANK = ['down' => 0, 'degraded' => 1, 'up' => 2];

    public function __construct(private UptimeService $uptime) {}

    public function index(Request $request)
    {
        $endpoints = MonitoredEndpoint::with('client')
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->get()
            ->sortBy(fn ($endpoint) => sprintf('%d-%d-%s', $endpoint->is_active ? 0 : 1, self::STATUS_RANK[$endpoint->current_status] ?? 3, strtolower($endpoint->name)))
            ->values();

        return response()->json([
            'summary' => $this->uptime->getSummary(),
            'data' => MonitoredEndpointResource::collection($endpoints)->resolve(),
        ]);
    }

    public function show(MonitoredEndpoint $endpoint)
    {
        return new MonitoredEndpointResource($this->detailed($endpoint));
    }

    /** Ping the site now and return the endpoint with the fresh result. */
    public function check(MonitoredEndpoint $endpoint)
    {
        $this->uptime->checkEndpoint($endpoint);

        return new MonitoredEndpointResource($this->detailed($endpoint->fresh()));
    }

    protected function detailed(MonitoredEndpoint $endpoint): MonitoredEndpoint
    {
        $endpoint->load(['client', 'uptimeChecks' => fn ($q) => $q->orderByDesc('checked_at')->orderByDesc('id')->limit(60)]);

        $recent = $endpoint->uptimeChecks()->where('checked_at', '>=', now()->subDay())->get(['status', 'response_time_ms']);
        $total = $recent->count();
        $endpoint->setAttribute('stats', [
            'checks_24h' => $total,
            'uptime_24h' => $total ? round($recent->whereIn('status', ['up', 'degraded'])->count() / $total * 100, 1) : null,
            'avg_response_ms_24h' => $total ? (int) round((float) $recent->avg('response_time_ms')) : null,
        ]);

        return $endpoint;
    }
}
