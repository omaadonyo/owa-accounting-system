<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Invoice') }} - {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: Inter, 'DejaVu Sans', sans-serif; font-size: 9px; color: #1a1a1a; line-height: 1.4; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .header-left { max-width: 60%; }
        .header-right { text-align: right; }
        .logo { max-height: 48px; margin-bottom: 6px; }
        .business-name { font-size: 18px; font-weight: 700; margin: 0 0 2px 0; color: #1a1a1a; }
        .business-address { margin: 0; color: #737373; font-size: 8px; line-height: 1.4; }
        .doc-title { font-size: 24px; font-weight: 700; margin: 0 0 2px 0; text-transform: uppercase; letter-spacing: 1px; }
        .doc-number { margin: 0; font-family: monospace; font-size: 11px; color: #737373; }
        hr { border: none; border-top: 1px solid #e5e5e5; margin: 12px 0; }
        .dates { display: flex; justify-content: space-between; font-size: 8px; }
        .dates-left p { margin: 1px 0; }
        .dates-label { color: #737373; }
        .dates-right { text-align: right; }
        .customer-name { font-weight: 600; color: #1a1a1a; }
        .customer-email { color: #737373; margin: 0; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 8px; border: 1px solid #e5e5e5; border-radius: 4px; overflow: hidden; }
        .items-table th { background: #f5f5f5; text-align: left; padding: 6px 8px; font-weight: 500; color: #737373; }
        .items-table th.right { text-align: right; }
        .items-table td { padding: 6px 8px; border-bottom: 1px solid #e5e5e5; }
        .items-table td.right { text-align: right; font-weight: 600; }
        .items-table tr:last-child td { border-bottom: none; }
        .totals { text-align: right; margin-top: 10px; font-size: 8px; }
        .totals p { margin: 1px 0; }
        .totals-label { display: inline-block; width: 80px; color: #737373; }
        .totals-hr { border: none; border-top: 1px solid #e5e5e5; margin: 3px 0; }
        .totals-grand { font-size: 13px; font-weight: 700; padding-top: 4px; }
        .receipts { margin-top: 12px; }
        .receipts-title { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #737373; margin: 0 0 4px 0; }
        .receipts-table { width: 100%; font-size: 8px; border-collapse: collapse; }
        .receipts-table td { padding: 2px 0; }
        .receipts-table td.right { text-align: right; }
        .receipts-table .divider { border-top: 1px solid #e5e5e5; padding-top: 4px; }
        .receipts-table .balance-label { font-weight: 600; }
        .receipts-none { color: #a3a3a3; font-style: italic; font-size: 8px; margin: 0; }
        .notes-section { margin-top: 12px; font-size: 8px; color: #737373; }
        .footer { margin-top: 24px; font-size: 7px; color: #a3a3a3; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            @if ($invoice->business->logo)
                <img src="{{ storage_path('app/public/' . $invoice->business->logo) }}" alt="Logo" class="logo">
            @endif
            <h1 class="business-name">{{ $invoice->business->name }}</h1>
            @if ($invoice->business->address)
                <p class="business-address">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->address))) !!}</p>
            @endif
        </div>
        <div class="header-right">
            <h1 class="doc-title">{{ __('INVOICE') }}</h1>
            <p class="doc-number">{{ $invoice->invoice_number }}</p>
        </div>
    </div>

    <hr>

    <div class="dates">
        <div class="dates-left">
            <p><span class="dates-label">{{ __('Issue Date:') }}</span> {{ $invoice->issue_date->format('d M Y') }}</p>
            @if ($invoice->due_date)
                <p><span class="dates-label">{{ __('Due Date:') }}</span> {{ $invoice->due_date->format('d M Y') }}</p>
            @endif
        </div>
        <div class="dates-right">
            <p class="customer-name">{{ $invoice->customer?->name ?? __('Walk-in Customer') }}</p>
            @if ($invoice->customer?->email)
                <p class="customer-email">{{ $invoice->customer->email }}</p>
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th class="right">{{ __('Qty') }}</th>
                <th class="right">{{ __('Price') }}</th>
                <th class="right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description ?: '—' }}</td>
                    <td class="right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="right">UGX {{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">UGX {{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:12px;color:#a3a3a3;">{{ __('No items.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <p><span class="totals-label">{{ __('Subtotal:') }}</span> UGX {{ number_format($invoice->subtotal, 2) }}</p>
        @if ((float) $invoice->discount_amount > 0)
            <p><span class="totals-label">{{ __('Discount:') }}</span> -UGX {{ number_format($invoice->discount_amount, 2) }}</p>
        @endif
        @if ((float) $invoice->tax_amount > 0)
            <p><span class="totals-label">{{ $invoice->tax_name ?? 'Tax' }} ({{ $invoice->tax_rate ?? 0 }}%):</span> UGX {{ number_format($invoice->tax_amount, 2) }}</p>
        @endif
        <hr class="totals-hr">
        <p class="totals-grand">UGX {{ number_format($invoice->total, 2) }}</p>
    </div>

    @php
        $payments = $invoice->payments()->orderBy('created_at', 'desc')->get();
        $paid = (float) $invoice->paid_amount;
        $balance = max(0, (float) $invoice->total - $paid);
    @endphp
    <div class="receipts">
        <h3 class="receipts-title">{{ __('Receipts') }}</h3>
        @if ($payments->isNotEmpty())
            <table class="receipts-table">
                @foreach ($payments as $p)
                    <tr>
                        <td style="font-family:monospace;">{{ $p->receipt_number }}</td>
                        <td class="right">UGX {{ number_format($p->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr><td colspan="2" class="divider"></td></tr>
                <tr>
                    <td class="balance-label">{{ __('Total') }}</td>
                    <td class="right">UGX {{ number_format($invoice->total, 2) }}</td>
                </tr>
                <tr>
                    <td class="balance-label">{{ __('Paid') }}</td>
                    <td class="right">UGX {{ number_format($paid, 2) }}</td>
                </tr>
                <tr>
                    <td class="balance-label">{{ __('Balance Due') }}</td>
                    <td class="right">UGX {{ number_format($balance, 2) }}</td>
                </tr>
            </table>
        @else
            <p class="receipts-none">{{ __('No receipts recorded yet.') }}</p>
        @endif
    </div>

    <div class="notes-section" style="display:flex;justify-content:space-between;align-items:flex-end;">
        <div style="max-width:70%;">
            @if ($invoice->notes)
                <p>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->notes))) !!}</p>
            @endif
            @if ($invoice->business->invoice_notes)
                <p style="margin-top:3px;font-style:italic;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->invoice_notes))) !!}</p>
            @endif
        </div>
        @php
            $qrData = $invoice->business->name . "\n" . $invoice->invoice_number . "\nUGX " . number_format($invoice->total, 2);
        @endphp
        @if (class_exists(\App\Helpers\QrCode::class))
            <div>
                {!! \App\Helpers\QrCode::generate($qrData, 90) !!}
            </div>
        @endif
    </div>

    <p class="footer">{{ __('Generated on :date', ['date' => now()->format('Y-m-d H:i:s')]) }}</p>
</body>
</html>
