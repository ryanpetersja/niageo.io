<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@niageo.io')->first();
        $snowJm = Client::where('company_name', 'Snow JM')->first();

        // RPT-202602-0001 - Snow JM - Generated (Sep 2025 - Jan 2026)
        $rpt1 = Report::updateOrCreate(
            ['report_number' => 'RPT-202602-0001'],
            [
                'client_id' => $snowJm->id,
                'created_by' => $admin->id,
                'title' => 'September 1, 2025 to January 31, 2026',
                'date_from' => '2025-09-01',
                'date_to' => '2026-01-31',
                'status' => 'generated',
                'commit_count' => 17,
                'repo_count' => 1,
                'server_count' => 0,
            ]
        );
        $this->seedStatusHistory($rpt1, $admin, [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Report created', 'created_at' => '2026-02-16 19:36:26'],
            ['from_status' => 'draft', 'to_status' => 'generated', 'notes' => '17 commits across 1 repo(s) summarized', 'created_at' => '2026-02-16 23:28:25'],
        ]);

        // RPT-202602-0002 - Snow JM - Generated (with server activity)
        $rpt2 = Report::updateOrCreate(
            ['report_number' => 'RPT-202602-0002'],
            [
                'client_id' => $snowJm->id,
                'created_by' => $admin->id,
                'title' => 'September 1, 2025 to January 31, 2026',
                'date_from' => '2025-09-01',
                'date_to' => '2026-01-31',
                'status' => 'generated',
                'commit_count' => 17,
                'repo_count' => 1,
                'server_count' => 1,
            ]
        );
        $this->seedStatusHistory($rpt2, $admin, [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Report created', 'created_at' => '2026-02-16 19:38:39'],
            ['from_status' => 'draft', 'to_status' => 'generated', 'notes' => '17 commits across 1 repo(s), 169 server commands from 1 server(s) summarized', 'created_at' => '2026-02-17 01:04:36'],
        ]);

        // RPT-202602-0003 - Snow JM - Draft (test)
        $rpt3 = Report::updateOrCreate(
            ['report_number' => 'RPT-202602-0003'],
            [
                'client_id' => $snowJm->id,
                'created_by' => $admin->id,
                'title' => 'qw',
                'date_from' => '2025-09-01',
                'date_to' => '2026-01-31',
                'status' => 'draft',
                'commit_count' => 0,
                'repo_count' => 0,
                'server_count' => 0,
            ]
        );
        $this->seedStatusHistory($rpt3, $admin, [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Report created', 'created_at' => '2026-02-16 19:56:08'],
        ]);

        // RPT-202602-0005 - Snow JM - Draft
        $rpt5 = Report::updateOrCreate(
            ['report_number' => 'RPT-202602-0005'],
            [
                'client_id' => $snowJm->id,
                'created_by' => $admin->id,
                'title' => '3bcbddc',
                'date_from' => '2025-09-01',
                'date_to' => '2026-01-31',
                'status' => 'draft',
                'commit_count' => 0,
                'repo_count' => 0,
                'server_count' => 0,
            ]
        );
        $this->seedStatusHistory($rpt5, $admin, [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Report created', 'created_at' => '2026-02-16 23:06:23'],
        ]);

        // RPT-202602-0006 - Snow JM - Draft
        $rpt6 = Report::updateOrCreate(
            ['report_number' => 'RPT-202602-0006'],
            [
                'client_id' => $snowJm->id,
                'created_by' => $admin->id,
                'title' => '3bcbddc',
                'date_from' => '2025-08-01',
                'date_to' => '2026-01-31',
                'status' => 'draft',
                'commit_count' => 0,
                'repo_count' => 0,
                'server_count' => 0,
            ]
        );
        $this->seedStatusHistory($rpt6, $admin, [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Report created', 'created_at' => '2026-02-16 23:09:11'],
        ]);

        // RPT-202602-0007 - Snow JM - Draft
        $rpt7 = Report::updateOrCreate(
            ['report_number' => 'RPT-202602-0007'],
            [
                'client_id' => $snowJm->id,
                'created_by' => $admin->id,
                'title' => 'August 2025- October 2025',
                'date_from' => '2025-08-01',
                'date_to' => '2025-10-31',
                'status' => 'draft',
                'commit_count' => 0,
                'repo_count' => 0,
                'server_count' => 0,
            ]
        );
        $this->seedStatusHistory($rpt7, $admin, [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Report created', 'created_at' => '2026-02-17 01:34:08'],
        ]);
    }

    private function seedStatusHistory(Report $report, User $admin, array $entries): void
    {
        ReportStatusHistory::where('report_id', $report->id)->delete();

        foreach ($entries as $entry) {
            ReportStatusHistory::create(array_merge($entry, [
                'report_id' => $report->id,
                'changed_by' => $admin->id,
            ]));
        }
    }
}
