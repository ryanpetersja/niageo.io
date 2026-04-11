<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->report_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }

        .header { padding: 30px 40px; border-bottom: 3px solid #4f46e5; }
        .header-flex { display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: top; width: 50%; }
        .header-right { display: table-cell; vertical-align: top; width: 50%; text-align: right; }
        .company-name { font-size: 22px; font-weight: bold; color: #1f2937; margin-bottom: 4px; }
        .company-detail { font-size: 10px; color: #6b7280; }
        .logo { max-height: 60px; max-width: 200px; margin-bottom: 8px; }

        .report-title { font-size: 28px; font-weight: bold; color: #4f46e5; }
        .report-number { font-size: 14px; color: #6b7280; margin-top: 2px; }

        .content { padding: 30px 40px 60px 40px; }

        .meta-table { display: table; width: 100%; margin-bottom: 25px; }
        .meta-left { display: table-cell; vertical-align: top; width: 50%; }
        .meta-right { display: table-cell; vertical-align: top; width: 50%; }
        .meta-label { font-size: 10px; text-transform: uppercase; color: #9ca3af; font-weight: 600; letter-spacing: 0.5px; }
        .meta-value { font-size: 13px; color: #1f2937; margin-bottom: 12px; }
        .client-name { font-size: 15px; font-weight: 600; color: #1f2937; }

        .report-heading { font-size: 13px; font-weight: 600; color: #1f2937; margin-bottom: 10px; margin-top: 20px; page-break-after: avoid; }

        .stats-bar { display: table; width: 100%; margin-bottom: 25px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; page-break-inside: avoid; }
        .stat-item { display: table-cell; text-align: center; padding: 12px 8px; border-right: 1px solid #e5e7eb; background: #f9fafb; }
        .stat-item:last-child { border-right: none; }
        .stat-number { font-size: 20px; font-weight: 700; color: #4f46e5; }
        .stat-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: 600; margin-top: 2px; }

        .category { margin-bottom: 16px; padding: 12px 14px; border-radius: 4px; page-break-inside: avoid; }
        .category-features { border-left: 4px solid #22c55e; background: #f0fdf4; }
        .category-bugs { border-left: 4px solid #ef4444; background: #fef2f2; }
        .category-improvements { border-left: 4px solid #3b82f6; background: #eff6ff; }
        .category-security { border-left: 4px solid #a855f7; background: #faf5ff; }
        .category-infrastructure { border-left: 4px solid #6b7280; background: #f9fafb; }

        .category-title { font-size: 12px; font-weight: 700; margin-bottom: 6px; }
        .category-features .category-title { color: #15803d; }
        .category-bugs .category-title { color: #b91c1c; }
        .category-improvements .category-title { color: #1d4ed8; }
        .category-security .category-title { color: #7e22ce; }
        .category-infrastructure .category-title { color: #374151; }

        .category ul { margin: 0; padding-left: 16px; }
        .category li { font-size: 11px; color: #374151; margin-bottom: 3px; }

        .notes { margin-top: 20px; padding: 12px; background: #f9fafb; border-radius: 6px; page-break-inside: avoid; }
        .notes-label { font-size: 10px; text-transform: uppercase; color: #9ca3af; font-weight: 600; margin-bottom: 4px; }
        .notes-text { font-size: 11px; color: #4b5563; }

        .invoice-ref { margin-top: 15px; padding: 10px 14px; background: #eef2ff; border-radius: 6px; border: 1px solid #c7d2fe; page-break-inside: avoid; }
        .invoice-ref-text { font-size: 11px; color: #4338ca; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 15px 40px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-flex">
            <div class="header-left">
                @if($branding->logo_path && file_exists(storage_path('app/public/' . $branding->logo_path)))
                    <img src="{{ storage_path('app/public/' . $branding->logo_path) }}" class="logo" alt="Logo">
                @endif
                <div class="company-name">{{ $branding->company_name }}</div>
                @if($branding->address)<div class="company-detail">{!! nl2br(e($branding->address)) !!}</div>@endif
                @if($branding->phone)<div class="company-detail">{{ $branding->phone }}</div>@endif
                @if($branding->email)<div class="company-detail">{{ $branding->email }}</div>@endif
                @if($branding->website)<div class="company-detail">{{ $branding->website }}</div>@endif
            </div>
            <div class="header-right">
                <div class="report-title">PLATFORM ACTIVITY REPORT</div>
                <div class="report-number">{{ $report->report_number }}</div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="meta-table">
            <div class="meta-left">
                <div class="meta-label">Prepared For</div>
                <div class="client-name">{{ $report->client->company_name }}</div>
                @if($report->client->billing_email)
                    <div class="company-detail">{{ $report->client->billing_email }}</div>
                @endif
            </div>
            <div class="meta-right">
                <div class="meta-label">Report Title</div>
                <div class="meta-value">{{ $report->title }}</div>
                <div class="meta-label">Report Period</div>
                <div class="meta-value">{{ $report->date_from->format('F d, Y') }} — {{ $report->date_to->format('F d, Y') }}</div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number">{{ $report->commit_count }}</div>
                <div class="stat-label">Commits</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $report->repo_count }}</div>
                <div class="stat-label">Repositories</div>
            </div>
            @if($report->server_count > 0)
                <div class="stat-item">
                    <div class="stat-number">{{ $report->raw_server_activity ? count($report->raw_server_activity) : 0 }}</div>
                    <div class="stat-label">Server Actions</div>
                </div>
            @endif
            <div class="stat-item">
                <div class="stat-number">{{ $report->summary_item_count }}</div>
                <div class="stat-label">Deliverables</div>
            </div>
            @if($report->uptime_score !== null)
                <div class="stat-item">
                    <div class="stat-number" style="color: #16a34a;">{{ number_format($report->uptime_score, 2) }}%</div>
                    <div class="stat-label">Uptime</div>
                </div>
            @endif
            <div class="stat-item">
                <div class="stat-number">{{ $report->date_from->diffInDays($report->date_to) }}</div>
                <div class="stat-label">Days</div>
            </div>
        </div>

        <!-- Services Provided -->
        @if($report->service_snapshot && count($report->service_snapshot) > 0)
            <div class="report-heading">Services Provided</div>
            <div style="margin-bottom: 20px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; page-break-inside: avoid;">
                @foreach($report->service_snapshot as $service)
                    <div style="display: table; width: 100%; padding: 8px 14px; border-bottom: 1px solid #f3f4f6; {{ $loop->last ? 'border-bottom: none;' : '' }}">
                        <div style="display: table-cell; vertical-align: middle; width: 50%;">
                            <span style="font-size: 12px; font-weight: 600; color: #1f2937;">{{ $service['display_name'] }}</span>
                        </div>
                        <div style="display: table-cell; vertical-align: middle; width: 50%; text-align: right;">
                            <span style="font-size: 11px; color: #6b7280;">{{ $service['metric_text'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Development Summary -->
        @if($report->ai_summary)
            <div class="report-heading">Development Activity Summary</div>

            @php
                $categoryMeta = [
                    'features' => ['label' => 'Features Delivered', 'class' => 'category-features'],
                    'bugs' => ['label' => 'Bug Fixes', 'class' => 'category-bugs'],
                    'improvements' => ['label' => 'Improvements & Optimizations', 'class' => 'category-improvements'],
                    'security' => ['label' => 'Security & Stability', 'class' => 'category-security'],
                    'infrastructure' => ['label' => 'Infrastructure & Maintenance', 'class' => 'category-infrastructure'],
                ];
            @endphp

            @foreach($categoryMeta as $key => $meta)
                @if(!empty($report->ai_summary[$key]))
                    <div class="category {{ $meta['class'] }}">
                        <div class="category-title">{{ $meta['label'] }}</div>
                        <ul>
                            @foreach($report->ai_summary[$key] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        @endif

        <!-- Server Activity Summary -->
        @if($report->server_summary)
            <div class="report-heading">Server Activity Summary</div>

            @php
                $serverCategoryMeta = [
                    'features' => ['label' => 'Deployments & Updates', 'class' => 'category-features'],
                    'bugs' => ['label' => 'Server-Side Fixes', 'class' => 'category-bugs'],
                    'improvements' => ['label' => 'Performance & Optimization', 'class' => 'category-improvements'],
                    'security' => ['label' => 'Security & Certificates', 'class' => 'category-security'],
                    'infrastructure' => ['label' => 'Server Maintenance', 'class' => 'category-infrastructure'],
                ];
            @endphp

            @foreach($serverCategoryMeta as $key => $meta)
                @if(!empty($report->server_summary[$key]))
                    <div class="category {{ $meta['class'] }}">
                        <div class="category-title">{{ $meta['label'] }}</div>
                        <ul>
                            @foreach($report->server_summary[$key] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        @endif

        @if($report->notes)
            <div class="notes">
                <div class="notes-label">Notes</div>
                <div class="notes-text">{{ $report->notes }}</div>
            </div>
        @endif

        @if($report->invoice)
            <div class="invoice-ref">
                <div class="invoice-ref-text">Please see accompanying invoice {{ $report->invoice->invoice_number }} for the billing details associated with this report.</div>
            </div>
        @endif
    </div>

    @if($branding->footer_text)
        <div class="footer">{{ $branding->footer_text }}</div>
    @endif
</body>
</html>
