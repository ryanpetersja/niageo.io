<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class MonitoredEndpointResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'company_name' => $this->client->company_name,
            ]),
            'status' => $this->current_status ?: 'unknown',
            'is_active' => (bool) $this->is_active,
            'check_interval_minutes' => (int) $this->check_interval_minutes,
            'timeout_seconds' => (int) $this->timeout_seconds,
            'degraded_threshold_ms' => (int) $this->degraded_threshold_ms,
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'last_response_time_ms' => $this->last_response_time_ms,
            'last_status_code' => $this->last_status_code,
            'last_error_message' => $this->last_error_message,
            // Set by the controller on detail responses: uptime and response time over the last day.
            'stats' => $this->when(isset($this->stats), fn () => $this->stats),
            'recent_checks' => $this->whenLoaded('uptimeChecks', fn () => $this->uptimeChecks->map(fn ($check) => [
                'status' => $check->status,
                'response_time_ms' => $check->response_time_ms,
                'status_code' => $check->status_code,
                'error_message' => $check->error_message,
                'checked_at' => $check->checked_at?->toIso8601String(),
            ])->values()->all()),
        ];
    }
}
