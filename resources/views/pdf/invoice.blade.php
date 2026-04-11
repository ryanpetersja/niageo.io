<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
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

        .invoice-title { font-size: 22px; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }
        .invoice-number { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .status-badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 6px; }
        .status-draft { background: #f3f4f6; color: #374151; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #fef3c7; color: #92400e; }

        .content { padding: 28px 40px; }

        .meta-table { display: table; width: 100%; margin-bottom: 28px; }
        .meta-left { display: table-cell; vertical-align: top; width: 50%; }
        .meta-right { display: table-cell; vertical-align: top; width: 50%; }
        .meta-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; letter-spacing: 0.5px; }
        .meta-value { font-size: 12px; color: #1f2937; margin-bottom: 12px; }

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

        .notes { margin-top: 30px; padding: 15px; background: #f9fafb; border-radius: 6px; }
        .notes-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; margin-bottom: 4px; }
        .notes-text { font-size: 10px; color: #4b5563; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 15px 40px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 8px; color: #9ca3af; }
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

    @if($branding->footer_text)
        <div class="footer">{{ $branding->footer_text }}</div>
    @endif
</body>
</html>
