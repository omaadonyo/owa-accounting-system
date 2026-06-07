<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Quotation') }} - {{ $quotation->quotation_number }}</title>
    <style>
        @page { margin: 40px 35px; }
        body { font-family: Inter, 'DejaVu Sans', sans-serif; font-size: 9px; color: #1c1c1c; line-height: 1.5; margin: 0; padding: 0; }
        hr { border: none; border-top: 1px solid #e8e8e8; margin: 18px 0; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 16px; }
        .header-left { max-width: 55%; }
        .header-right { text-align: right; }
        .logo { max-height: 52px; margin-bottom: 6px; }
        .business-name { font-size: 18px; font-weight: 700; margin: 0 0 3px 0; color: #1c1c1c; letter-spacing: -0.3px; }
        .business-address { margin: 0; color: #8a8a8a; font-size: 7.5px; line-height: 1.5; }
        .doc-title { font-size: 20px; font-weight: 800; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 2px; color: #f97316; }
        .doc-number { margin: 0; font-size: 10px; color: #8a8a8a; letter-spacing: 0.5px; }

        .accent-bar { height: 3px; background: #f97316; width: 100%; margin: 0 0 18px 0; border-radius: 2px; }

        .info-grid { display: flex; justify-content: space-between; margin-bottom: 22px; }
        .info-block { }
        .info-title { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #f97316; margin: 0 0 6px 0; }
        .info-block p { margin: 2px 0; font-size: 8.5px; }
        .info-label { color: #8a8a8a; }
        .info-value { font-weight: 500; color: #1c1c1c; }
        .customer-name { font-weight: 600; color: #1c1c1c; font-size: 10px; }
        .customer-contact { color: #8a8a8a; margin: 2px 0; }

        .items-table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        .items-table thead { }
        .items-table th { background: #f97316; color: #fff; text-align: left; padding: 8px 10px; font-weight: 600; font-size: 8px; text-transform: uppercase; letter-spacing: 0.6px; }
        .items-table th.right { text-align: right; }
        .items-table th:first-child { border-radius: 4px 0 0 0; }
        .items-table th:last-child { border-radius: 0 4px 0 0; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
        .items-table td.right { text-align: right; font-weight: 600; }
        .items-table tbody tr:nth-child(even) td { background: #fafafa; }
        .items-table tbody tr:last-child td:first-child { border-radius: 0 0 0 4px; }
        .items-table tbody tr:last-child td:last-child { border-radius: 0 0 4px 0; }
        .item-desc { max-width: 280px; }
        .item-desc-title { font-weight: 500; color: #1c1c1c; }
        .item-desc-sub { font-size: 7.5px; color: #8a8a8a; margin-top: 1px; }

        .totals-section { margin-top: 16px; margin-left: auto; width: 240px; }
        .totals-section table { width: 100%; font-size: 8.5px; }
        .totals-section td { padding: 3px 10px; }
        .totals-section td.label { color: #8a8a8a; text-align: right; }
        .totals-section td.value { text-align: right; font-weight: 600; }
        .totals-section .discount { color: #ef4444; }
        .totals-hr td { border-top: 1px solid #e0e0e0; padding-top: 6px; }
        .totals-grand td.label { font-size: 11px; font-weight: 700; color: #1c1c1c; padding-top: 6px; }
        .totals-grand td.value { font-size: 14px; font-weight: 800; color: #f97316; padding-top: 6px; }

        .footer-section { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e8e8e8; }
        .notes { max-width: 65%; font-size: 7.5px; color: #8a8a8a; line-height: 1.6; }
        .notes p { margin: 0 0 4px 0; }
        .notes strong { color: #5a5a5a; }
        .qr img { max-width: 85px; max-height: 85px; }

        .footer-meta { margin-top: 20px; font-size: 7px; color: #bfbfbf; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            @if ($quotation->business->logo)
                <img src="{{ storage_path('app/public/' . $quotation->business->logo) }}" alt="Logo" class="logo">
            @endif
            <h1 class="business-name">{{ $quotation->business->name }}</h1>
            @if ($quotation->business->address)
                <p class="business-address">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->address))) !!}</p>
            @endif
        </div>
        <div class="header-right">
            <h1 class="doc-title">{{ __('QUOTATION') }}</h1>
            <p class="doc-number">{{ $quotation->quotation_number }}</p>
        </div>
    </div>

    <div class="accent-bar"></div>

    <div class="info-grid">
        <div class="info-block">
            <h3 class="info-title">{{ __('Dates') }}</h3>
            <p><span class="info-label">{{ __('Issue Date:') }}</span> <span class="info-value">{{ $quotation->issue_date->format('d M Y') }}</span></p>
            @if ($quotation->valid_until)
                <p><span class="info-label">{{ __('Valid Until:') }}</span> <span class="info-value">{{ $quotation->valid_until->format('d M Y') }}</span></p>
            @endif
        </div>
        <div class="info-block" style="text-align:right;">
            <h3 class="info-title">{{ __('Bill To') }}</h3>
            <p class="customer-name">{{ $quotation->customer?->name ?? __('Walk-in Customer') }}</p>
            @if ($quotation->customer?->email)
                <p class="customer-contact">{{ $quotation->customer->email }}</p>
            @endif
            @if ($quotation->customer?->phone)
                <p class="customer-contact">{{ $quotation->customer->phone }}</p>
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
            @forelse ($quotation->items as $item)
                <tr>
                    <td class="item-desc">
                        <span class="item-desc-title">{{ $item->description ?: '—' }}</span>
                    </td>
                    <td class="right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="right">UGX {{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">UGX {{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:16px;color:#bfbfbf;">{{ __('No items.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-section">
        <table>
            <tr>
                <td class="label">{{ __('Subtotal') }}</td>
                <td class="value">UGX {{ number_format($quotation->subtotal, 2) }}</td>
            </tr>
            @if ((float) $quotation->discount_amount > 0)
                <tr>
                    <td class="label discount">{{ __('Discount') }}</td>
                    <td class="value discount">-UGX {{ number_format($quotation->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if ((float) $quotation->tax_amount > 0)
                <tr>
                    <td class="label">{{ $quotation->tax_name ?? 'Tax' }} ({{ $quotation->tax_rate ?? 0 }}%)</td>
                    <td class="value">UGX {{ number_format($quotation->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="totals-hr"><td colspan="2"></td></tr>
            <tr class="totals-grand">
                <td class="label">{{ __('Total') }}</td>
                <td class="value">UGX {{ number_format($quotation->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer-section">
        <div class="notes">
            @if ($quotation->notes)
                <p><strong>{{ __('Notes') }}</strong></p>
                <p>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->notes))) !!}</p>
            @endif
            @if ($quotation->business->quotes_notes)
                <p style="font-style:italic;margin-top:4px;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->quotes_notes))) !!}</p>
            @endif
        </div>
        @php
            $qrData = $quotation->business->name . "\n" . $quotation->quotation_number . "\nUGX " . number_format($quotation->total, 2);
        @endphp
        @if (class_exists(\App\Helpers\QrCode::class))
            <div class="qr">
                <img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 180) }}" alt="QR Code">
            </div>
        @endif
    </div>

    <p class="footer-meta">{{ __('Generated on :date', ['date' => now()->format('Y-m-d H:i:s')]) }}</p>
</body>
</html>
