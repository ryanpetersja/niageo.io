<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportFeedback;
use App\Models\ReportPreference;
use App\Models\ReportStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{
    private const VALID_TRANSITIONS = [
        'draft' => ['generated'],
        'generated' => ['sent', 'draft'],
        'sent' => ['archived'],
    ];

    public function __construct(
        private GitHubService $gitHubService,
        private ClaudeService $claudeService,
        private SshActivityService $sshService,
    ) {}

    public function create(array $data): Report
    {
        return DB::transaction(function () use ($data) {
            $data['report_number'] = $data['report_number'] ?? Report::generateReportNumber();
            $data['created_by'] = $data['created_by'] ?? auth()->id();
            $data['status'] = 'draft';

            $report = Report::create($data);

            ReportStatusHistory::create([
                'report_id' => $report->id,
                'from_status' => null,
                'to_status' => 'draft',
                'changed_by' => auth()->id(),
                'notes' => 'Report created',
            ]);

            return $report;
        });
    }

    public function update(Report $report, array $data): Report
    {
        if (in_array($report->status, ['sent', 'archived'])) {
            throw new \RuntimeException('Sent or archived reports cannot be edited.');
        }

        $report->update($data);
        return $report->fresh();
    }

    public function generate(Report $report): Report
    {
        if ($report->status !== 'draft') {
            throw new \RuntimeException('Only draft reports can be generated.');
        }

        $since = $report->date_from->startOfDay()->toIso8601String();
        $until = $report->date_to->endOfDay()->toIso8601String();
        $clientName = $report->client->company_name;

        // Fetch GitHub commits
        $commits = $this->gitHubService->fetchCommitsForClient(
            $report->client_id,
            $since,
            $until
        );

        // Fetch SSH server activity
        $serverActivity = $this->sshService->fetchActivityForClient(
            $report->client_id,
            $since,
            $until
        );

        // Require at least one data source
        if (empty($commits) && empty($serverActivity)) {
            throw new \RuntimeException('No commits or server activity found for the selected date range.');
        }

        $repos = collect($commits)->pluck('repo')->unique()->count();
        $servers = \App\Models\ClientServer::where('client_id', $report->client_id)->where('is_active', true)->count();

        // Fetch report preferences for AI guidance
        $preferences = ReportPreference::getSettings()->rules ?? [];

        // Generate AI summaries for available data
        $commitSummary = !empty($commits)
            ? $this->claudeService->summarizeCommits($commits, $clientName, $preferences)
            : null;

        $serverSummary = !empty($serverActivity)
            ? $this->claudeService->summarizeServerActivity($serverActivity, $clientName, $preferences)
            : null;

        return DB::transaction(function () use ($report, $commits, $commitSummary, $repos, $serverActivity, $serverSummary, $servers) {
            $updateData = [
                'raw_commits' => $commits ?: null,
                'ai_summary' => $commitSummary,
                'commit_count' => count($commits),
                'repo_count' => $repos,
                'raw_server_activity' => $serverActivity ?: null,
                'server_summary' => $serverSummary,
                'server_count' => $servers,
                'status' => 'generated',
                'generated_at' => now(),
                'service_snapshot' => $this->snapshotServices($report),
            ];

            $report->update($updateData);

            $parts = [];
            if (!empty($commits)) {
                $parts[] = count($commits) . ' commits across ' . $repos . ' repo(s)';
            }
            if (!empty($serverActivity)) {
                $parts[] = count($serverActivity) . ' server commands from ' . $servers . ' server(s)';
            }

            ReportStatusHistory::create([
                'report_id' => $report->id,
                'from_status' => 'draft',
                'to_status' => 'generated',
                'changed_by' => auth()->id(),
                'notes' => implode(', ', $parts) . ' summarized',
            ]);

            return $report->fresh();
        });
    }

    public function regenerate(Report $report): Report
    {
        if (!in_array($report->status, ['generated', 'draft'])) {
            throw new \RuntimeException('Only draft or generated reports can be regenerated.');
        }

        DB::transaction(function () use ($report) {
            if ($report->status === 'generated') {
                $report->update([
                    'status' => 'draft',
                    'raw_commits' => null,
                    'ai_summary' => null,
                    'raw_server_activity' => null,
                    'server_summary' => null,
                    'commit_count' => 0,
                    'repo_count' => 0,
                    'server_count' => 0,
                    'generated_at' => null,
                ]);

                ReportStatusHistory::create([
                    'report_id' => $report->id,
                    'from_status' => 'generated',
                    'to_status' => 'draft',
                    'changed_by' => auth()->id(),
                    'notes' => 'Report reset for regeneration',
                ]);
            }
        });

        return $this->generate($report->fresh());
    }

    public function transition(Report $report, string $toStatus, ?string $notes = null): Report
    {
        $fromStatus = $report->status;
        $allowed = self::VALID_TRANSITIONS[$fromStatus] ?? [];

        if (!in_array($toStatus, $allowed)) {
            throw new \RuntimeException("Cannot transition from '{$fromStatus}' to '{$toStatus}'.");
        }

        return DB::transaction(function () use ($report, $fromStatus, $toStatus, $notes) {
            $report->update(['status' => $toStatus]);

            ReportStatusHistory::create([
                'report_id' => $report->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => auth()->id(),
                'notes' => $notes,
            ]);

            return $report->fresh();
        });
    }

    public function markAsSent(Report $report, string $email): Report
    {
        $report = $this->transition($report, 'sent', "Sent to {$email}");

        $report->update([
            'sent_at' => now(),
            'sent_to_email' => $email,
        ]);

        return $report->fresh();
    }

    public function delete(Report $report): void
    {
        if ($report->status === 'sent') {
            throw new \RuntimeException('Sent reports cannot be deleted.');
        }

        $report->delete();
    }

    public function getValidTransitions(Report $report): array
    {
        return self::VALID_TRANSITIONS[$report->status] ?? [];
    }

    public function submitFeedback(Report $report, string $feedback): ReportFeedback
    {
        return ReportFeedback::create([
            'report_id' => $report->id,
            'user_id' => auth()->id(),
            'feedback' => $feedback,
        ]);
    }

    public function previewFeedback(Report $report, array $data): array
    {
        $summaryType = $data['summary_type'];
        $currentSummary = $summaryType === 'ai_summary' ? $report->ai_summary : $report->server_summary;

        if (empty($currentSummary)) {
            throw new \RuntimeException('No summary available to revise.');
        }

        $clientName = $report->client->company_name;

        $proposed = $this->claudeService->reviseSummary(
            $currentSummary,
            $data['feedback'],
            $clientName,
            $data['category'] ?? null,
            isset($data['item_index']) ? (int) $data['item_index'] : null,
            $data['item_text'] ?? null,
        );

        return [
            'original' => $currentSummary,
            'proposed' => $proposed,
            'summary_type' => $summaryType,
        ];
    }

    public function acceptFeedback(Report $report, array $data, array $proposedSummary): ReportFeedback
    {
        return DB::transaction(function () use ($report, $data, $proposedSummary) {
            $summaryType = $data['summary_type'];
            $field = $summaryType === 'ai_summary' ? 'ai_summary' : 'server_summary';

            $report->update([$field => $proposedSummary]);

            $feedback = ReportFeedback::create([
                'report_id' => $report->id,
                'user_id' => auth()->id(),
                'feedback' => $data['feedback'],
                'summary_type' => $summaryType,
                'category' => $data['category'] ?? null,
                'item_index' => $data['item_index'] ?? null,
                'item_text' => $data['item_text'] ?? null,
                'proposed_summary' => $proposedSummary,
                'resolution' => 'accepted',
            ]);

            return $feedback;
        });
    }

    public function rejectFeedback(Report $report, array $data): ReportFeedback
    {
        return ReportFeedback::create([
            'report_id' => $report->id,
            'user_id' => auth()->id(),
            'feedback' => $data['feedback'],
            'summary_type' => $data['summary_type'] ?? null,
            'category' => $data['category'] ?? null,
            'item_index' => $data['item_index'] ?? null,
            'item_text' => $data['item_text'] ?? null,
            'resolution' => 'rejected',
        ]);
    }

    private function snapshotServices(Report $report): ?array
    {
        $services = $report->client->services()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($services->isEmpty()) {
            return null;
        }

        $days = $report->date_from->diffInDays($report->date_to);

        return $services->map(fn ($s) => [
            'display_name' => $s->display_name,
            'service_type' => $s->service_type,
            'config' => $s->config,
            'days' => $days,
            'metric_text' => $this->calculateMetricText($s, $days),
        ])->toArray();
    }

    private function calculateMetricText($service, int $days): string
    {
        if ($service->service_type === 'backups') {
            $frequency = $service->config['frequency'] ?? 'daily';
            return match ($frequency) {
                'weekly' => (int) ceil($days / 7) . ' weekly backups generated',
                'monthly' => (int) ceil($days / 30) . ' monthly backups generated',
                default => $days . ' daily backups generated',
            };
        }

        return $days . ' days of ' . $service->display_name . ' provided';
    }

    public function processUnprocessedFeedback(): void
    {
        $unprocessed = ReportFeedback::where('processed', false)->get();

        if ($unprocessed->isEmpty()) {
            return;
        }

        $feedbackTexts = $unprocessed->pluck('feedback')->all();
        $currentRules = ReportPreference::getSettings()->rules ?? [];

        DB::transaction(function () use ($feedbackTexts, $currentRules, $unprocessed) {
            $updatedRules = $this->claudeService->distillFeedback($feedbackTexts, $currentRules);

            $preferences = ReportPreference::getSettings();
            $preferences->update([
                'rules' => $updatedRules,
                'last_distilled_at' => now(),
            ]);

            ReportFeedback::whereIn('id', $unprocessed->pluck('id'))
                ->update([
                    'processed' => true,
                    'processed_at' => now(),
                ]);
        });
    }
}
