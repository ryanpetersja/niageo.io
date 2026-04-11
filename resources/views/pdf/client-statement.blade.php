<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Statement - {{ $client->company_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.6; }

        .header { padding: 30px 40px; border-bottom: 3px solid #4f46e5; }
        .header-flex { display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: top; width: 50%; }
        .header-right { display: table-cell; vertical-align: top; width: 50%; text-align: right; }
        .company-name { font-size: 22px; font-weight: bold; color: #1f2937; margin-bottom: 4px; }
        .company-detail { font-size: 10px; color: #6b7280; }
        .logo { max-height: 60px; max-width: 200px; margin-bottom: 8px; }

        .statement-title { font-size: 22px; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }

        .content { padding: 30px 40px; }

        .meta-table { display: table; width: 100%; margin-bottom: 24px; }
        .meta-left { display: table-cell; vertical-align: top; width: 50%; }
        .meta-right { display: table-cell; vertical-align: top; width: 50%; }
        .meta-label { font-size: 10px; text-transform: uppercase; color: #9ca3af; font-weight: 600; letter-spacing: 0.5px; }
        .meta-value { font-size: 13px; color: #1f2937; margin-bottom: 12px; }
        .client-name { font-size: 15px; font-weight: 600; color: #1f2937; }

        .summary-bar { display: table; width: 100%; margin-bottom: 28px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; }
        .summary-box { display: table-cell; width: 25%; text-align: center; padding: 14px 8px; border-right: 1px solid #e5e7eb; }
        .summary-box:last-child { border-right: none; }
        .summary-box-label { font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px; }
        .summary-box-value { font-size: 18px; font-weight: 700; color: #1f2937; }
        .summary-box-value.outstanding { color: #dc2626; }
        .summary-box-value.paid { color: #059669; }

        table.invoices { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.invoices thead th { background: #f9fafb; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; color: #6b7280; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
        table.invoices thead th.text-right { text-align: right; }
        table.invoices tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 12px; }
        table.invoices tbody td.text-right { text-align: right; }

        .invoice-row td { border-bottom: 1px solid #e5e7eb; }
        .payment-row td { background: #fafafa; font-size: 11px; color: #6b7280; border-bottom: 1px solid #f3f4f6; }
        .payment-label { padding-left: 30px !important; font-style: italic; }

        .status-badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-overdue { background: #fee2e2; color: #991b1b; }

        .totals-row td { border-top: 2px solid #e5e7eb; font-weight: 700; font-size: 13px; color: #1f2937; padding-top: 12px; }

        .empty-message { text-align: center; padding: 40px; color: #9ca3af; font-size: 14px; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 15px 40px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 9px; color: #9ca3af; }
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
                <div class="statement-title">ACCOUNT STATEMENT</div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="meta-table">
            <div class="meta-left">
                <div class="meta-label">Client</div>
                <div class="client-name">{{ $client->company_name }}</div>
                @if($client->billing_email)
                    <div class="company-detail">{{ $client->billing_email }}</div>
                @endif
            </div>
            <div class="meta-right">
                <div class="meta-label">Statement Period</div>
                <div class="meta-value">{{ \Carbon\Carbon::parse($from)->format('F d, Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('F d, Y') }}</div>
                <div class="meta-label">Generated</div>
                <div class="meta-value">{{ now()->format('F d, Y') }}</div>
            </div>
        </div>

        <div class="summary-bar">
            <div class="summary-box">
                <div class="summary-box-label">Total Invoiced</div>
                <div class="summary-box-value">${{ number_format($totalInvoiced, 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">Total Paid</div>
                <div class="summary-box-value paid">${{ number_format($totalPaid, 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">Outstanding Balance</div>
                <div class="summary-box-value outstanding">${{ number_format($totalOutstanding, 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">Invoice Count</div>
                <div class="summary-box-value">{{ $invoices->count() }}</div>
            </div>
        </div>

        @if($invoices->isEmpty())
            <div class="empty-message">No invoices found for the selected period.</div>
        @else
            <table class="invoices">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr class="invoice-row">
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
                            <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                            <td><span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></td>
                            <td class="text-right">${{ number_format($invoice->total, 2) }}</td>
                            <td class="text-right">${{ number_format($invoice->amount_paid, 2) }}</td>
                            <td class="text-right">${{ number_format($invoice->balance_due, 2) }}</td>
                        </tr>
                        @foreach($invoice->payments as $payment)
                            <tr class="payment-row">
                                <td class="payment-label">Payment</td>
                                <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td>{{ $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : '—' }}</td>
                                <td>{{ $payment->reference ?: '—' }}</td>
                                <td class="text-right"></td>
                                <td class="text-right" style="color: #059669;">${{ number_format($payment->amount, 2) }}</td>
                                <td class="text-right"></td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="totals-row">
                        <td colspan="4" style="text-align: right;">Totals</td>
                        <td class="text-right">${{ number_format($totalInvoiced, 2) }}</td>
                        <td class="text-right" style="color: #059669;">${{ number_format($totalPaid, 2) }}</td>
                        <td class="text-right" style="color: #dc2626;">${{ number_format($totalOutstanding, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    @if($branding->footer_text)
        <div class="footer">{{ $branding->footer_text }}</div>
    @endif
</body>
</html>
