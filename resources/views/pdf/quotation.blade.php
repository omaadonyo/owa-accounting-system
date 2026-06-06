<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Quotation') }} - {{ $quotation->quotation_number }}</title>
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
        .notes { margin-top: 16px; font-size: 10px; color: #6b7280; }
        .footer { margin-top: 32px; font-size: 9px; color: #9ca3af; text-align: center; }
        .qr { text-align: right; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="business-info">
            @if ($quotation->business->logo)
                <img src="{{ storage_path('app/public/' . $quotation->business->logo) }}" alt="Logo" class="logo">
            @endif
            <h1>{{ $quotation->business->name }}</h1>
            @if ($quotation->business->address)
                <p>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->address))) !!}</p>
            @endif
        </div>
        <div class="doc-title">
            <h1>{{ __('QUOTATION') }}</h1>
            <p>{{ $quotation->quotation_number }}</p>
        </div>
    </div>

    <hr>

    <div class="dates">
        <div class="left">
            <p><span>{{ __('Issue Date:') }}</span> {{ $quotation->issue_date->format('d M Y') }}</p>
            @if ($quotation->valid_until)
                <p><span>{{ __('Valid Until:') }}</span> {{ $quotation->valid_until->format('d M Y') }}</p>
            @endif
        </div>
        <div class="right">
            <p class="name">{{ $quotation->customer?->name ?? __('Walk-in Customer') }}</p>
            @if ($quotation->customer?->email)
                <p class="email">{{ $quotation->customer->email }}</p>
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
            @forelse ($quotation->items as $item)
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
        <p><span class="line">{{ __('Subtotal:') }}</span> UGX {{ number_format($quotation->subtotal, 2) }}</p>
        @if ((float) $quotation->discount_amount > 0)
            <p><span class="line">{{ __('Discount:') }}</span> -UGX {{ number_format($quotation->discount_amount, 2) }}</p>
        @endif
        @if ((float) $quotation->tax_amount > 0)
            <p><span class="line">{{ $quotation->tax_name ?? 'Tax' }} ({{ $quotation->tax_rate ?? 0 }}%):</span> UGX {{ number_format($quotation->tax_amount, 2) }}</p>
        @endif
        <p class="grand">UGX {{ number_format($quotation->total, 2) }}</p>
    </div>

    <div class="notes">
        @if ($quotation->notes)
            <p>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->notes))) !!}</p>
        @endif
        @if ($quotation->business->quotes_notes)
            <p style="margin-top:4px;font-style:italic;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->quotes_notes))) !!}</p>
        @endif
    </div>

    @php
        $qrData = $quotation->business->name . "\n" . $quotation->quotation_number . "\nUGX " . number_format($quotation->total, 2);
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
