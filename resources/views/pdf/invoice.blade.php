<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Invoice') }} - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; margin: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .business-info h1 { font-size: 20px; margin: 0 0 2px 0; }
        .business-info p { margin: 0; color: #6b7280; font-size: 10px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 26px; margin: 0 0 2px 0; text-transform: uppercase; letter-spacing: 1px; }
        .doc-title p { margin: 0; font-family: monospace; font-size: 12px; color: #6b7280; }
        hr { border: none; border-top: 1px solid #e5e7eb; margin: 16px 0; }
        .dates { display: flex; justify-content: space-between; font-size: 10px; }
        .dates .left p { margin: 2px 0; }
        .dates .left span { color: #6b7280; }
        .dates .right { text-align: right; }
        .dates .right .name { font-weight: 700; color: #1a1a1a; }
        .dates .right .email { color: #6b7280; }
        .logo { max-height: 48px; margin-bottom: 8px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 10px; }
        table.items th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        table.items th.right { text-align: right; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        table.items td.right { text-align: right; font-weight: 600; }
        .totals { text-align: right; margin-top: 12px; font-size: 10px; }
        .totals p { margin: 2px 0; }
        .totals .line { display: inline-block; width: 100px; color: #6b7280; }
        .totals .grand { font-size: 15px; font-weight: 700; margin-top: 4px; padding-top: 4px; border-top: 2px solid #1a1a1a; }
        .receipts { margin-top: 16px; }
        .receipts h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin: 0 0 6px 0; }
        .receipts table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .receipts table td { padding: 4px 0; }
        .receipts table td.right { text-align: right; }
        .receipts .total-row td { font-weight: 700; border-top: 1px solid #e5e7eb; padding-top: 6px; }
        .notes { margin-top: 16px; font-size: 10px; color: #6b7280; }
        .footer { margin-top: 32px; font-size: 9px; color: #9ca3af; text-align: center; }
        .qr { text-align: right; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="business-info">
            @if ($invoice->business->logo)
                <img src="{{ storage_path('app/public/' . $invoice->business->logo) }}" alt="Logo" class="logo">
            @endif
            <h1>{{ $invoice->business->name }}</h1>
            @if ($invoice->business->address)
                <p>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->address))) !!}</p>
            @endif
        </div>
        <div class="doc-title">
            <h1>{{ __('INVOICE') }}</h1>
            <p>{{ $invoice->invoice_number }}</p>
        </div>
    </div>

    <hr>

    <div class="dates">
        <div class="left">
            <p><span>{{ __('Issue Date:') }}</span> {{ $invoice->issue_date->format('d M Y') }}</p>
            @if ($invoice->due_date)
                <p><span>{{ __('Due Date:') }}</span> {{ $invoice->due_date->format('d M Y') }}</p>
            @endif
        </div>
        <div class="right">
            <p class="name">{{ $invoice->customer?->name ?? __('Walk-in Customer') }}</p>
            @if ($invoice->customer?->email)
                <p class="email">{{ $invoice->customer->email }}</p>
            @endif
        </div>
    </div>

    <table class="items">
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
                    <td colspan="4" style="text-align:center;padding:20px;color:#9ca3af;">{{ __('No items.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <p><span class="line">{{ __('Subtotal:') }}</span> UGX {{ number_format($invoice->subtotal, 2) }}</p>
        @if ((float) $invoice->discount_amount > 0)
            <p><span class="line">{{ __('Discount:') }}</span> -UGX {{ number_format($invoice->discount_amount, 2) }}</p>
        @endif
        @if ((float) $invoice->tax_amount > 0)
            <p><span class="line">{{ $invoice->tax_name ?? 'Tax' }} ({{ $invoice->tax_rate ?? 0 }}%):</span> UGX {{ number_format($invoice->tax_amount, 2) }}</p>
        @endif
        <p class="grand">UGX {{ number_format($invoice->total, 2) }}</p>
    </div>

    @php
        $payments = $invoice->payments()->orderBy('created_at', 'desc')->get();
        $paid = (float) $invoice->paid_amount;
        $balance = max(0, (float) $invoice->total - $paid);
    @endphp
    <div class="receipts">
        <h3>{{ __('Receipts') }}</h3>
        @if ($payments->isNotEmpty())
            <table>
                @foreach ($payments as $p)
                    <tr>
                        <td>{{ $p->receipt_number }}</td>
                        <td class="right">UGX {{ number_format($p->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>{{ __('Total') }}</td>
                    <td class="right">UGX {{ number_format($invoice->total, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ __('Paid') }}</td>
                    <td class="right">UGX {{ number_format($paid, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>{{ __('Balance Due') }}</td>
                    <td class="right">UGX {{ number_format($balance, 2) }}</td>
                </tr>
            </table>
        @else
            <p style="color:#9ca3af;font-style:italic;">{{ __('No receipts recorded yet.') }}</p>
        @endif
    </div>

    <div class="notes">
        @if ($invoice->notes)
            <p>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->notes))) !!}</p>
        @endif
        @if ($invoice->business->invoice_notes)
            <p style="margin-top:4px;font-style:italic;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->invoice_notes))) !!}</p>
        @endif
    </div>

    @php
        $qrData = $invoice->business->name . "\n" . $invoice->invoice_number . "\nUGX " . number_format($invoice->total, 2);
    @endphp
    @if (class_exists(\App\Helpers\QrCode::class))
        <div class="qr">
            {!! \App\Helpers\QrCode::generate($qrData, 100) !!}
        </div>
    @endif

    <div class="footer">
        {{ __('Generated on :date', ['date' => now()->format('Y-m-d H:i:s')]) }}
    </div>
</body>
</html>
