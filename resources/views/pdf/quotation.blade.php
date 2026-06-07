<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ __('Quotation') }} - {{ $quotation->quotation_number }}</title>

@php
    $accent = $quotation->business->accent_color ?? '#f97316';
    $hex = ltrim($accent, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $dark = sprintf('#%02x%02x%02x', (int)($r * 0.55), (int)($g * 0.55), (int)($b * 0.55));
@endphp

<style>
@page { size: A4; margin: 0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Inter, 'DejaVu Sans', Arial, Helvetica, sans-serif; color:#333; background:#fff; font-size:12px; line-height:1.4; }

/* HEADER TABLE */
.header-table { width:100%; border-collapse:collapse; }
.header-table td { vertical-align:top; }
.company-cell { padding:32px 40px; }
.sidebar-cell { width:310px; background:{{ $dark }}; color:#fff; padding:30px; vertical-align:top; }

.logo-img { max-height:44px; margin-bottom:6px; }
.business-name { font-size:28px; font-weight:700; letter-spacing:.4px; }
.business-sub { margin-top:2px; font-size:11px; letter-spacing:2.5px; color:#666; text-transform:uppercase; }

.doc-title { font-size:36px; font-weight:700; letter-spacing:1px; margin-bottom:22px; }
.doc-meta { font-size:11.5px; line-height:23px; color:rgba(255,255,255,.8); }
.doc-meta strong { color:#fff; }

/* BILLING TABLE */
.billing-table { width:100%; border-collapse:collapse; margin-top:18px; }
.billing-table td { padding:4px 40px; vertical-align:top; width:50%; }
.section-title { font-size:10px; font-weight:700; color:#666; letter-spacing:1px; margin-bottom:7px; text-transform:uppercase; }
.bill-text { font-size:12.5px; line-height:20px; }

/* ITEMS TABLE */
.table-wrap { padding:18px 40px 0; }
.invoice-table { width:100%; border-collapse:collapse; }
.invoice-table thead th { background:{{ $dark }}; color:#fff; padding:12px; font-size:10px; letter-spacing:1px; text-transform:uppercase; text-align:left; }
.invoice-table thead th:nth-child(2),
.invoice-table thead th:nth-child(3),
.invoice-table thead th:nth-child(4) { text-align:right; }
.invoice-table tbody td { padding:12px; border-bottom:1px solid #e6e6e6; vertical-align:top; font-size:11.5px; }
.invoice-table tbody td:nth-child(2),
.invoice-table tbody td:nth-child(3),
.invoice-table tbody td:nth-child(4) { text-align:right; }
.item-title { font-size:12px; font-weight:700; margin-bottom:2px; }

/* BOTTOM TABLE */
.bottom-table { width:100%; border-collapse:collapse; margin-top:20px; }
.bottom-table td { vertical-align:top; padding:0 40px; }
.bottom-table .left-col { width:55%; }
.bottom-table .right-col { width:45%; }

/* TOTALS */
.total-table { width:100%; border-collapse:collapse; }
.total-table td { padding:6px 0; font-size:11.5px; }
.total-table td:last-child { text-align:right; font-weight:600; }
.total-table .discount td { color:#dc2626; }
.grand-total td { border-top:2px solid {{ $dark }}; padding-top:10px; font-size:17px; font-weight:700; }
.grand-total td:last-child { color:{{ $accent }}; }

/* NOTES */
.notes-section { margin-top:16px; }
.notes-text { font-size:10px; color:#666; line-height:17px; margin-top:4px; }
.notes-text p { margin-bottom:2px; }

/* QR */
.qr-wrap { text-align:right; margin-top:16px; }
.qr-box { width:100px; height:100px; border:1px solid #dcdcdc; display:inline-block; text-align:center; vertical-align:middle; line-height:100px; background:#fff; }
.qr-box img { vertical-align:middle; max-width:96px; max-height:96px; }
.qr-title { margin-top:5px; font-size:9px; font-weight:700; letter-spacing:1px; }
.qr-note { font-size:9px; color:#777; margin-top:2px; }

/* SIGNATURE */
.signature-row { margin:24px 40px 0; border-top:1px solid #e8e8e8; padding-top:18px; }
.signature-table { width:100%; border-collapse:collapse; }
.signature-table td { vertical-align:bottom; }
.thank-you { font-size:28px; font-weight:300; color:{{ $accent }}; }
.signature-wrap { text-align:right; }
.signature-name { font-size:24px; font-family:'DejaVu Sans', cursive; margin-bottom:3px; }
.signature-role { font-size:10px; color:#666; }

/* FOOTER */
.footer { margin:30px 40px 20px; padding-top:14px; border-top:1px solid #e8e8e8; font-size:9.5px; color:#666; }
.footer-table { width:100%; border-collapse:collapse; }
.footer-table td { }
</style>
</head>
<body>

<!-- HEADER -->
<table class="header-table">
<tr>
    <td class="company-cell">
        @if ($quotation->business->logo)
            <img src="{{ storage_path('app/public/' . $quotation->business->logo) }}" alt="Logo" class="logo-img">
        @endif
        <div class="business-name">{{ $quotation->business->name }}</div>
        @if ($quotation->business->address)
            <div class="business-sub">{{ __('OFFICIAL DOCUMENT') }}</div>
        @endif
    </td>
    <td class="sidebar-cell">
        <div class="doc-title">{{ __('QUOTATION') }}</div>
        <div class="doc-meta">
            <strong>{{ __('Quotation') }} #</strong> {{ $quotation->quotation_number }}<br>
            <strong>{{ __('Issue Date') }}</strong> {{ $quotation->issue_date->format('d M Y') }}<br>
            @if ($quotation->valid_until)
                <strong>{{ __('Valid Until') }}</strong> {{ $quotation->valid_until->format('d M Y') }}
            @endif
        </div>
    </td>
</tr>
</table>

<!-- BILLING -->
<table class="billing-table">
<tr>
    <td>
        <div class="section-title">{{ __('QUOTE TO') }}</div>
        <div class="bill-text">
            {{ $quotation->customer?->name ?? __('Walk-in Customer') }}<br>
            @if ($quotation->customer?->email){{ $quotation->customer->email }}<br>@endif
            @if ($quotation->customer?->phone){{ $quotation->customer->phone }}<br>@endif
            @if ($quotation->customer?->address){{ $quotation->customer->address }}<br>@endif
        </div>
    </td>
    <td>
        <div class="section-title">{{ __('FROM') }}</div>
        <div class="bill-text">
            {{ $quotation->business->name }}<br>
            @if ($quotation->business->email){{ $quotation->business->email }}<br>@endif
            @if ($quotation->business->address)
                {!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->address))) !!}<br>
            @endif
        </div>
    </td>
</tr>
</table>

<!-- ITEMS -->
<div class="table-wrap">
<table class="invoice-table">
<thead>
<tr>
    <th>{{ __('Description') }}</th>
    <th>{{ __('Price') }}</th>
    <th>{{ __('Qty') }}</th>
    <th>{{ __('Total') }}</th>
</tr>
</thead>
<tbody>
@forelse ($quotation->items as $item)
    <tr>
        <td><div class="item-title">{{ $item->description ?: '—' }}</div></td>
        <td>UGX {{ number_format($item->unit_price, 2) }}</td>
        <td>{{ number_format($item->quantity, 2) }}</td>
        <td>UGX {{ number_format($item->total, 2) }}</td>
    </tr>
@empty
    <tr>
        <td colspan="4" style="text-align:center;padding:16px;color:#bfbfbf;">{{ __('No items.') }}</td>
    </tr>
@endforelse
</tbody>
</table>
</div>

<!-- BOTTOM -->
<table class="bottom-table">
<tr>
    <td class="left-col">
        @if ($quotation->notes || $quotation->business->quotes_notes)
            <div class="notes-section">
                <div class="section-title">{{ __('NOTES') }}</div>
                <div class="notes-text">
                    @if ($quotation->notes)
                        {!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->notes))) !!}
                    @endif
                    @if ($quotation->business->quotes_notes)
                        <p style="font-style:italic;margin-top:4px;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->quotes_notes))) !!}</p>
                    @endif
                </div>
            </div>
        @endif
    </td>
    <td class="right-col">
        <table class="total-table">
            <tr>
                <td>{{ __('Sub Total') }}</td>
                <td>UGX {{ number_format($quotation->subtotal, 2) }}</td>
            </tr>
            @if ((float) $quotation->discount_amount > 0)
                <tr class="discount">
                    <td>{{ __('Discount') }}</td>
                    <td>-UGX {{ number_format($quotation->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if ((float) $quotation->tax_amount > 0)
                <tr>
                    <td>{{ $quotation->tax_name ?? __('Tax') }} ({{ $quotation->tax_rate ?? 0 }}%)</td>
                    <td>UGX {{ number_format($quotation->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td>{{ __('GRAND TOTAL') }}</td>
                <td>UGX {{ number_format($quotation->total, 2) }}</td>
            </tr>
        </table>

        @php
            $qrData = $quotation->business->name . "\n" . $quotation->quotation_number . "\nUGX " . number_format($quotation->total, 2);
        @endphp
        @if (class_exists(\App\Helpers\QrCode::class))
            <div class="qr-wrap">
                <div class="qr-box">
                    <img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 200) }}" alt="QR">
                </div>
                <div class="qr-title">{{ __('SCAN TO VERIFY') }}</div>
                <div class="qr-note">{{ __('Secure document') }}</div>
            </div>
        @endif
    </td>
</tr>
</table>

<!-- SIGNATURE -->
<div class="signature-row">
<table class="signature-table">
<tr>
    <td><div class="thank-you">{{ __('Thank You!') }}</div></td>
    <td class="signature-wrap">
        <div class="signature-name">{{ $quotation->business->name }}</div>
        <div class="signature-role">{{ __('Authorized Signature') }}</div>
    </td>
</tr>
</table>
</div>

<!-- FOOTER -->
<div class="footer">
<table class="footer-table">
<tr>
    <td>{{ $quotation->business->name }}</td>
    <td style="text-align:center;">@if ($quotation->business->email){{ $quotation->business->email }}@endif</td>
    <td style="text-align:right;">{{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</td>
</tr>
</table>
</div>

</body>
</html>
