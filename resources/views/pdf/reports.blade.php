<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Reports') }} - {{ $business->name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; margin: 40px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .subtitle { color: #6b7280; margin-bottom: 24px; }
        .stats { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 28px; }
        .stat-box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 18px; min-width: 130px; flex: 1; }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 4px; }
        .stat-value { font-size: 18px; font-weight: 700; }
        .stat-sub { font-size: 9px; color: #9ca3af; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        th.right { text-align: right; }
        td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        td.right { text-align: right; }
        .section-title { font-size: 14px; font-weight: 700; margin-top: 24px; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #111; }
        .footer { margin-top: 32px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ __('Sales Report') }}</h1>
    <div class="subtitle">{{ $business->name }} &mdash; {{ $periodLabel }} &mdash; {{ now()->format('F j, Y') }}</div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-label">{{ __('Total Revenue') }}</div>
            <div class="stat-value">UGX {{ number_format($totalRevenue, 0) }}</div>
            <div class="stat-sub">{{ $paymentCount }} {{ __('payments') }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">{{ __('Total Invoiced') }}</div>
            <div class="stat-value">UGX {{ number_format($totalInvoiced, 0) }}</div>
            <div class="stat-sub">{{ $invoiceCount }} {{ __('invoices') }} &middot; {{ $paidInvoices }} {{ __('paid') }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">{{ __('Outstanding') }}</div>
            <div class="stat-value">UGX {{ number_format(max($totalInvoiced - $totalRevenue, 0), 0) }}</div>
            <div class="stat-sub">{{ $pendingInvoices }} {{ __('unpaid') }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">{{ __('Customers') }}</div>
            <div class="stat-value">{{ $customerCount }}</div>
            <div class="stat-sub">{{ $quotationCount }} {{ __('quotes') }} &middot; {{ $invoiceCount }} {{ __('invoices') }}</div>
        </div>
    </div>

    <div class="section-title">{{ __('Payment Method Breakdown') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('Method') }}</th>
                <th class="right">{{ __('Count') }}</th>
                <th class="right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($paymentMethodBreakdown as $method)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $method->payment_method)) }}</td>
                    <td class="right">{{ $method->count }}</td>
                    <td class="right">UGX {{ number_format($method->total, 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;padding:30px;color:#9ca3af;">{{ __('No payments recorded.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">{{ __('Highest Invoices') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('Invoice') }}</th>
                <th>{{ __('Customer') }}</th>
                <th class="right">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topInvoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->customer?->name ?? __('Walk-in') }}</td>
                    <td class="right">UGX {{ number_format($invoice->total, 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;padding:30px;color:#9ca3af;">{{ __('No invoices yet.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">{{ __('Recent Payments') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('Receipt') }}</th>
                <th>{{ __('Invoice') }}</th>
                <th class="right">{{ __('Amount') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Method') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recentPayments as $payment)
                <tr>
                    <td>{{ $payment->receipt_number ?? '—' }}</td>
                    <td>{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                    <td class="right">UGX {{ number_format($payment->amount, 0) }}</td>
                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:30px;color:#9ca3af;">{{ __('No payments yet.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('Generated by :app on :date', ['app' => config('app.name'), 'date' => now()->format('Y-m-d H:i:s')]) }}
    </div>
</body>
</html>
