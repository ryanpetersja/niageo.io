<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->report_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.6; }

        .header { padding: 30px 40px; border-bottom: 3px solid #4f46e5; }
        .header-flex { display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: top; width: 50%; }
        .header-right { display: table-cell; vertical-align: top; width: 50%; text-align: right; }
        .company-name { font-size: 20px; font-weight: bold; color: #1f2937; margin-bottom: 4px; }
        .company-detail { font-size: 9px; color: #6b7280; line-height: 1.5; }
        .logo { max-height: 60px; max-width: 200px; margin-bottom: 8px; }

        .report-title { font-size: 22px; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }
        .report-number { font-size: 12px; color: #6b7280; margin-top: 4px; }

        .content { padding: 28px 40px 60px 40px; }

        .meta-table { display: table; width: 100%; margin-bottom: 24px; }
        .meta-left { display: table-cell; vertical-align: top; width: 50%; }
        .meta-right { display: table-cell; vertical-align: top; width: 50%; }
        .meta-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; letter-spacing: 0.5px; }
        .meta-value { font-size: 12px; color: #1f2937; margin-bottom: 12px; }
        .client-name { font-size: 14px; font-weight: bold; color: #1f2937; }

        .report-heading { font-size: 13px; font-weight: bold; color: #1f2937; margin-bottom: 10px; margin-top: 22px; page-break-after: avoid; }

        .stats-bar { display: table; width: 100%; margin-bottom: 24px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; page-break-inside: avoid; }
        .stat-item { display: table-cell; text-align: center; padding: 12px 8px; border-right: 1px solid #e5e7eb; background: #f9fafb; }
        .stat-item:last-child { border-right: none; }
        .stat-number { font-size: 18px; font-weight: bold; color: #4f46e5; }
        .stat-label { font-size: 8px; text-transform: uppercase; color: #9ca3af; font-weight: bold; margin-top: 2px; letter-spacing: 0.3px; }

        .category { margin-bottom: 14px; padding: 10px 14px; border-radius: 4px; page-break-inside: avoid; }
        .category-features { border-left: 4px solid #22c55e; background: #f0fdf4; }
        .category-bugs { border-left: 4px solid #ef4444; background: #fef2f2; }
        .category-improvements { border-left: 4px solid #3b82f6; background: #eff6ff; }
        .category-security { border-left: 4px solid #a855f7; background: #faf5ff; }
        .category-infrastructure { border-left: 4px solid #6b7280; background: #f9fafb; }

        .category-title { font-size: 11px; font-weight: bold; margin-bottom: 5px; }
        .category-features .category-title { color: #15803d; }
        .category-bugs .category-title { color: #b91c1c; }
        .category-improvements .category-title { color: #1d4ed8; }
        .category-security .category-title { color: #7e22ce; }
        .category-infrastructure .category-title { color: #374151; }

        .category ul { margin: 0; padding-left: 16px; }
        .category li { font-size: 10px; color: #374151; margin-bottom: 3px; line-height: 1.5; }

        .notes { margin-top: 20px; padding: 12px; background: #f9fafb; border-radius: 6px; page-break-inside: avoid; }
        .notes-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; margin-bottom: 4px; }
        .notes-text { font-size: 10px; color: #4b5563; }

        .invoice-ref { margin-top: 15px; padding: 10px 14px; background: #eef2ff; border-radius: 6px; border: 1px solid #c7d2fe; page-break-inside: avoid; }
        .invoice-ref-text { font-size: 10px; color: #4338ca; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 15px 40px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 8px; color: #9ca3af; }

        /* Invoice page styles */
        .invoice-page { page-break-before: always; }
        .invoice-title { font-size: 22px; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }
        .invoice-number { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .status-badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 6px; }
        .status-draft { background: #f3f4f6; color: #374151; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #fef3c7; color: #92400e; }
        .bill-to-name { font-size: 14px; font-weight: bold; color: #1f2937; }
        table.line-items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.line-items thead th { background: #f9fafb; padding: 10px 12px; text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: bold; border-bottom: 2px solid #e5e7eb; }
        table.line-items thead th.text-right { text-align: right; }
        table.line-items tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
        table.line-items tbody td.text-right { text-align: right; }
        .totals { width: 280px; margin-left: auto; }
        .total-row { display: table; width: 100%; padding: 5px 0; }
        .total-label { display: table-cell; text-align: right; padding-right: 20px; color: #6b7280; font-size: 11px; }
        .total-value { display: table-cell; text-align: right; font-size: 11px; width: 100px; }
        .total-row.grand { border-top: 2px solid #e5e7eb; padding-top: 8px; margin-top: 4px; }
        .total-row.grand .total-label { font-weight: bold; color: #1f2937; font-size: 13px; }
        .total-row.grand .total-value { font-weight: bold; color: #1f2937; font-size: 13px; }
        .total-row.balance .total-label { font-weight: bold; color: #dc2626; font-size: 12px; }
        .total-row.balance .total-value { font-weight: bold; color: #dc2626; font-size: 12px; }
        .total-row.paid .total-value { color: #059669; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-flex">
            <div class="header-left">
                @if($branding->logo_path)
                    @php
                        $logoFullPath = storage_path('app/public/' . $branding->logo_path);
                        $logoBase64 = null;
                        if (file_exists($logoFullPath)) {
                            $logoMime = mime_content_type($logoFullPath);
                            $logoBase64 = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoFullPath));
                        }
                    @endphp
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                @endif
                @if(empty($logoBase64))
                    <div class="company-name">{{ $branding->company_name }}</div>
                @endif
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

        <!-- Maintenance Activity Summary -->
        @if($report->ai_summary)
            <div class="report-heading">Maintenance Activity Summary</div>

            @php
                $categoryMeta = [
                    'features' => ['label' => 'Features Delivered', 'class' => 'category-features'],
                    'bugs' => ['label' => 'Bug Fixes', 'class' => 'category-bugs'],
                    'improvements' => ['label' => 'Improvements & Optimizations', 'class' => 'category-improvements'],
                    'security' => ['label' => 'Security & Stability', 'class' => 'category-security'],
                    'infrastructure' => ['label' => 'Infrastructure & Maintenance', 'class' => 'category-infrastructure'],
                ];

                $rawCommits = $report->raw_commits ?? [];
                $commitRefs = $report->ai_summary['commit_refs'] ?? [];
                $itemDates = $report->ai_summary['item_dates'] ?? [];

                function getItemDate($category, $index, $commitRefs, $rawCommits, $itemDates) {
                    // Manual date takes priority (stored as YYYY-MM-DD)
                    $manual = $itemDates[$category][$index] ?? null;
                    if ($manual) {
                        $ts = strtotime($manual);
                        return $ts ? date('M j', $ts) : $manual;
                    }

                    // Fall back to commit ref dates
                    $refs = $commitRefs[$category][$index] ?? [];
                    if (empty($refs)) return null;
                    $dates = [];
                    foreach ($refs as $sha) {
                        foreach ($rawCommits as $c) {
                            if (isset($c['sha']) && str_starts_with($c['sha'], $sha) && !empty($c['date'])) {
                                $dates[] = strtotime($c['date']);
                                break;
                            }
                        }
                    }
                    if (empty($dates)) return null;
                    sort($dates);
                    $earliest = date('M j', $dates[0]);
                    $latest = date('M j', end($dates));
                    return $earliest === $latest ? $earliest : $earliest . '–' . $latest;
                }
            @endphp

            @foreach($categoryMeta as $key => $meta)
                @if(!empty($report->ai_summary[$key]))
                    <div class="category {{ $meta['class'] }}">
                        <div class="category-title">{{ $meta['label'] }}</div>
                        <ul>
                            @foreach($report->ai_summary[$key] as $idx => $item)
                                @php $itemDate = getItemDate($key, $idx, $commitRefs, $rawCommits, $itemDates); @endphp
                                <li>@if($itemDate)<span style="color: #9ca3af; font-size: 9px;">{{ $itemDate }}</span> @endif{{ $item }}</li>
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
                $serverItemDates = $report->server_summary['item_dates'] ?? [];
            @endphp

            @foreach($serverCategoryMeta as $key => $meta)
                @if(!empty($report->server_summary[$key]))
                    <div class="category {{ $meta['class'] }}">
                        <div class="category-title">{{ $meta['label'] }}</div>
                        <ul>
                            @foreach($report->server_summary[$key] as $idx => $item)
                                @php
                                    $srvDateRaw = $serverItemDates[$key][$idx] ?? null;
                                    $srvDate = $srvDateRaw ? (($ts = strtotime($srvDateRaw)) ? date('M j', $ts) : $srvDateRaw) : null;
                                @endphp
                                <li>@if($srvDate)<span style="color: #9ca3af; font-size: 9px;">{{ $srvDate }}</span> @endif{{ $item }}</li>
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
                <div class="invoice-ref-text">The associated invoice ({{ $report->invoice->invoice_number }}) is included on the following page.</div>
            </div>
        @endif
    </div>

    {{-- Invoice page --}}
    @if($report->invoice)
        @php $invoice = $report->invoice; @endphp
        <div class="invoice-page">
            <div class="header">
                <div class="header-flex">
                    <div class="header-left">
                        @if($logoBase64 ?? false)
                            <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                        @else
                            <div class="company-name">{{ $branding->company_name }}</div>
                        @endif
                        @if($branding->address)<div class="company-detail">{!! nl2br(e($branding->address)) !!}</div>@endif
                        @if($branding->phone)<div class="company-detail">{{ $branding->phone }}</div>@endif
                        @if($branding->email)<div class="company-detail">{{ $branding->email }}</div>@endif
                        @if($branding->website)<div class="company-detail">{{ $branding->website }}</div>@endif
                    </div>
                    <div class="header-right">
                        <div class="invoice-title">INVOICE</div>
                        <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                        <div class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="meta-table">
                    <div class="meta-left">
                        <div class="meta-label">Bill To</div>
                        <div class="bill-to-name">{{ $invoice->client->company_name }}</div>
                        @if($invoice->client->billing_email)
                            <div class="company-detail">{{ $invoice->client->billing_email }}</div>
                        @endif
                    </div>
                    <div class="meta-right">
                        <div class="meta-label">Issue Date</div>
                        <div class="meta-value">{{ $invoice->issue_date->format('F d, Y') }}</div>
                        <div class="meta-label">Due Date</div>
                        <div class="meta-value">{{ $invoice->due_date->format('F d, Y') }}</div>
                    </div>
                </div>

                <table class="line-items">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->lineItems as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right">${{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="totals">
                    <div class="total-row">
                        <span class="total-label">Subtotal</span>
                        <span class="total-value">${{ number_format($invoice->subtotal, 2) }}</span>
                    </div>
                    @if($invoice->tax_rate > 0)
                        <div class="total-row">
                            <span class="total-label">Tax ({{ $invoice->tax_rate }}%)</span>
                            <span class="total-value">${{ number_format($invoice->tax_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="total-row grand">
                        <span class="total-label">Total</span>
                        <span class="total-value">${{ number_format($invoice->total, 2) }}</span>
                    </div>
                    @if($invoice->amount_paid > 0)
                        <div class="total-row paid">
                            <span class="total-label">Amount Paid</span>
                            <span class="total-value">${{ number_format($invoice->amount_paid, 2) }}</span>
                        </div>
                        <div class="total-row balance">
                            <span class="total-label">Balance Due</span>
                            <span class="total-value">${{ number_format($invoice->balance_due, 2) }}</span>
                        </div>
                    @endif
                </div>

                @if($invoice->notes)
                    <div class="notes">
                        <div class="notes-label">Notes</div>
                        <div class="notes-text">{{ $invoice->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($branding->footer_text)
        <div class="footer">{{ $branding->footer_text }}</div>
    @endif
</body>
</html>
