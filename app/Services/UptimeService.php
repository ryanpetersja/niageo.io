<?php

namespace App\Services;

use App\Models\MonitoredEndpoint;
use App\Models\UptimeCheck;
use Illuminate\Support\Facades\Http;

class UptimeService
{
    public function checkEndpoint(MonitoredEndpoint $endpoint): UptimeCheck
    {
        $startTime = microtime(true);
        $status = 'down';
        $responseTimeMs = null;
        $statusCode = null;
        $errorMessage = null;

        try {
            $response = Http::timeout($endpoint->timeout_seconds)
                ->connectTimeout($endpoint->timeout_seconds)
                ->get($endpoint->url);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();

            if ($response->successful()) {
                $status = $responseTimeMs > $endpoint->degraded_threshold_ms ? 'degraded' : 'up';
            } else {
                $status = 'down';
                $errorMessage = "HTTP {$statusCode}";
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $errorMessage = 'Connection failed: ' . $e->getMessage();
        } catch (\Exception $e) {
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();
        }

        $now = now();

        $check = UptimeCheck::create([
            'monitored_endpoint_id' => $endpoint->id,
            'status' => $status,
            'response_time_ms' => $responseTimeMs,
            'status_code' => $statusCode,
            'error_message' => $errorMessage,
            'checked_at' => $now,
        ]);

        $endpoint->update([
            'current_status' => $status,
            'last_checked_at' => $now,
            'last_response_time_ms' => $responseTimeMs,
            'last_status_code' => $statusCode,
            'last_error_message' => $errorMessage,
        ]);

        return $check;
    }

    public function checkAllDue(): int
    {
        $endpoints = MonitoredEndpoint::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('last_checked_at')
                    ->orWhereRaw('last_checked_at < DATE_SUB(NOW(), INTERVAL check_interval_minutes MINUTE)');
            })
            ->get();

        foreach ($endpoints as $endpoint) {
            $this->checkEndpoint($endpoint);
        }

        return $endpoints->count();
    }

    public function getSummary(): array
    {
        $counts = MonitoredEndpoint::where('is_active', true)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN current_status = 'up' THEN 1 ELSE 0 END) as up_count,
                SUM(CASE WHEN current_status = 'degraded' THEN 1 ELSE 0 END) as degraded_count,
                SUM(CASE WHEN current_status = 'down' THEN 1 ELSE 0 END) as down_count
            ")
            ->first();

        return [
            'up' => (int) $counts->up_count,
            'degraded' => (int) $counts->degraded_count,
            'down' => (int) $counts->down_count,
            'total' => (int) $counts->total,
        ];
    }
}
