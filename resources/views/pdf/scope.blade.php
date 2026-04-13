<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $scope->scope_number }}</title>
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

        .scope-title { font-size: 22px; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }
        .scope-number { font-size: 12px; color: #6b7280; margin-top: 4px; }

        .content { padding: 28px 40px 60px 40px; }

        .meta-table { display: table; width: 100%; margin-bottom: 24px; }
        .meta-left { display: table-cell; vertical-align: top; width: 50%; }
        .meta-right { display: table-cell; vertical-align: top; width: 50%; }
        .meta-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; letter-spacing: 0.5px; }
        .meta-value { font-size: 12px; color: #1f2937; margin-bottom: 12px; }
        .client-name { font-size: 14px; font-weight: bold; color: #1f2937; }

        .stats-bar { display: table; width: 100%; margin-bottom: 24px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; page-break-inside: avoid; }
        .stat-item { display: table-cell; text-align: center; padding: 12px 8px; border-right: 1px solid #e5e7eb; background: #f9fafb; }
        .stat-item:last-child { border-right: none; }
        .stat-number { font-size: 18px; font-weight: bold; color: #4f46e5; }
        .stat-label { font-size: 8px; text-transform: uppercase; color: #9ca3af; font-weight: bold; margin-top: 2px; letter-spacing: 0.3px; }

        .section-heading { font-size: 13px; font-weight: bold; color: #1f2937; margin-bottom: 10px; margin-top: 22px; page-break-after: avoid; }

        .section-block { margin-bottom: 16px; padding: 10px 14px; border-left: 4px solid #4f46e5; background: #f9fafb; border-radius: 4px; page-break-inside: avoid; }
        .section-block-title { font-size: 11px; font-weight: bold; color: #4338ca; margin-bottom: 5px; }
        .section-block-text { font-size: 10px; color: #374151; line-height: 1.6; }
        .section-block ul { margin: 0; padding-left: 16px; }
        .section-block li { font-size: 10px; color: #374151; margin-bottom: 3px; line-height: 1.5; }

        .category-heading { font-size: 12px; font-weight: bold; color: #4338ca; background: #eef2ff; padding: 6px 12px; border-radius: 4px; margin-top: 18px; margin-bottom: 8px; page-break-after: avoid; }

        table.scope-items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.scope-items thead th { background: #f9fafb; padding: 8px 12px; text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: bold; border-bottom: 2px solid #e5e7eb; }
        table.scope-items thead th.text-right { text-align: right; }
        table.scope-items tbody td { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; font-size: 11px; vertical-align: top; }
        table.scope-items tbody td.text-right { text-align: right; }
        .item-title { font-weight: bold; color: #1f2937; }
        .item-description { font-size: 9px; color: #6b7280; margin-top: 2px; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-mandatory { background: #fee2e2; color: #991b1b; }
        .badge-optional { background: #dbeafe; color: #1e40af; }
        .badge-recommended { background: #d1fae5; color: #065f46; }

        .subtotal-row { text-align: right; padding: 6px 12px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; }

        .pricing-summary { width: 300px; margin-left: auto; margin-top: 20px; page-break-inside: avoid; }
        .pricing-row { display: table; width: 100%; padding: 5px 0; }
        .pricing-label { display: table-cell; text-align: right; padding-right: 20px; color: #6b7280; font-size: 11px; }
        .pricing-value { display: table-cell; text-align: right; font-size: 11px; width: 100px; }
        .pricing-row.grand { border-top: 2px solid #e5e7eb; padding-top: 8px; margin-top: 4px; }
        .pricing-row.grand .pricing-label { font-weight: bold; color: #1f2937; font-size: 13px; }
        .pricing-row.grand .pricing-value { font-weight: bold; color: #1f2937; font-size: 13px; }

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
    {{-- Branding header --}}
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
                <div class="scope-title">PROJECT SCOPE</div>
                <div class="scope-number">{{ $scope->scope_number }}</div>
            </div>
        </div>
    </div>

    <div class="content">
        {{-- Meta section --}}
        <div class="meta-table">
            <div class="meta-left">
                <div class="meta-label">Prepared For</div>
                <div class="client-name">{{ $scope->client->company_name }}</div>
                @if($scope->client->billing_email)
                    <div class="company-detail">{{ $scope->client->billing_email }}</div>
                @endif
            </div>
            <div class="meta-right">
                <div class="meta-label">Scope Title</div>
                <div class="meta-value">{{ $scope->title }}</div>
                <div class="meta-label">Date</div>
                <div class="meta-value">{{ $scope->created_at->format('F d, Y') }}</div>
            </div>
        </div>

        {{-- Stats bar --}}
        @php
            $totalItems = $scope->items->count();
            $mandatoryItems = $scope->items->where('is_mandatory', true)->count();
        @endphp
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number">{{ $totalItems }}</div>
                <div class="stat-label">Total Items</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $mandatoryItems }}</div>
                <div class="stat-label">Mandatory Items</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $scope->currency_symbol }}{{ number_format($scope->total_price, 2) }}</div>
                <div class="stat-label">Total Value</div>
            </div>
        </div>

        {{-- Content sections --}}
        @php
            $sectionMeta = [
                'purpose_statement' => 'Purpose Statement',
                'problem_description' => 'Problem Description',
                'solution_overview' => 'Solution Overview',
                'goals_objectives' => 'Goals & Objectives',
                'assumptions' => 'Assumptions',
                'out_of_scope' => 'Out of Scope',
                'timeline_summary' => 'Timeline Summary',
                'next_steps' => 'Next Steps',
            ];
            $sections = $scope->sections ?? [];
        @endphp

        @foreach($sectionMeta as $key => $label)
            @if(!empty($sections[$key]))
                <div class="section-block">
                    <div class="section-block-title">{{ $label }}</div>
                    @if(is_array($sections[$key]))
                        <ul>
                            @foreach($sections[$key] as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="section-block-text">{!! nl2br(e($sections[$key])) !!}</div>
                    @endif
                </div>
            @endif
        @endforeach

        {{-- Scope Items grouped by category --}}
        @if($scope->items->count() > 0)
            <div class="section-heading">Scope Items</div>

            @php
                $grouped = $scope->items->groupBy('category');
            @endphp

            @foreach($grouped as $category => $items)
                <div class="category-heading">{{ $category ?: 'General' }}</div>

                <table class="scope-items">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="width: 100px;">Type</th>
                            <th class="text-right" style="width: 100px;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <div class="item-title">{{ $item->title }}</div>
                                    @if($item->description)
                                        <div class="item-description">{{ $item->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($item->is_mandatory)
                                        <span class="badge badge-mandatory">Mandatory</span>
                                    @elseif($item->is_recommended)
                                        <span class="badge badge-recommended">Recommended</span>
                                    @elseif($item->is_optional)
                                        <span class="badge badge-optional">Optional</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ $scope->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="subtotal-row">
                    Subtotal: {{ $scope->currency_symbol }}{{ number_format($items->sum('price'), 2) }}
                </div>
            @endforeach

            {{-- Pricing summary --}}
            @php
                $mandatoryTotal = $scope->items->where('is_mandatory', true)->sum('price');
                $optionalTotal = $scope->items->where('is_optional', true)->sum('price');
                $recommendedTotal = $scope->items->where('is_recommended', true)->sum('price');
            @endphp
            <div class="pricing-summary">
                <div class="pricing-row">
                    <span class="pricing-label">Mandatory Total</span>
                    <span class="pricing-value">{{ $scope->currency_symbol }}{{ number_format($mandatoryTotal, 2) }}</span>
                </div>
                @if($optionalTotal > 0)
                    <div class="pricing-row">
                        <span class="pricing-label">Optional Total</span>
                        <span class="pricing-value">{{ $scope->currency_symbol }}{{ number_format($optionalTotal, 2) }}</span>
                    </div>
                @endif
                @if($recommendedTotal > 0)
                    <div class="pricing-row">
                        <span class="pricing-label">Recommended Total</span>
                        <span class="pricing-value">{{ $scope->currency_symbol }}{{ number_format($recommendedTotal, 2) }}</span>
                    </div>
                @endif
                <div class="pricing-row grand">
                    <span class="pricing-label">Grand Total</span>
                    <span class="pricing-value">{{ $scope->currency_symbol }}{{ number_format($scope->total_price, 2) }}</span>
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if($scope->notes)
            <div class="notes">
                <div class="notes-label">Notes</div>
                <div class="notes-text">{!! nl2br(e($scope->notes)) !!}</div>
            </div>
        @endif

        {{-- Invoice reference --}}
        @if($scope->invoice)
            <div class="invoice-ref">
                <div class="invoice-ref-text">The associated invoice ({{ $scope->invoice->invoice_number }}) is included on the following page.</div>
            </div>
        @endif
    </div>

    {{-- Invoice page --}}
    @if($scope->invoice)
        @php $invoice = $scope->invoice; @endphp
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
