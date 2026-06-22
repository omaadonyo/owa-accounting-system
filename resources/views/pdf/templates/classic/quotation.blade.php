@php
$accent = $quotation->business->accent_color ?? '#f97316';
$hex = ltrim($accent, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
$dark = sprintf('#%02x%02x%02x', (int)($r * 0.55), (int)($g * 0.55), (int)($b * 0.55));
$title = $quotation->custom_title ?: __('QUOTATION');
$subtotal = $quotation->subtotal;
$taxAmount = $quotation->tax_amount;
$discountAmount = $quotation->discount_amount;
$whtAmount = $quotation->wht_amount;
$total = $quotation->total;
if ($quotation->tax_inclusive && $quotation->tax_rate > 0) { $taxAmount = round(($quotation->subtotal - $discountAmount) * ($quotation->tax_rate / 100), 2); $total = round($quotation->subtotal - $discountAmount + $taxAmount, 2); } else { $taxAmount = 0; $total = round($quotation->subtotal - $discountAmount, 2); }
@endphp
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{{ $title }} - {{ $quotation->quotation_number }}</title>
<style>
@page { size: A4; margin: 0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'DejaVu Sans', Arial, sans-serif; color:#333; font-size:12px; line-height:1.3; }
.header-table { width:100%; border-collapse:collapse; }
.header-table td { vertical-align:top; }
.company-cell { padding:32px 40px; }
.sidebar-cell { width:260px; background:{{ $dark }}; color:#fff; padding:30px; }
.logo-img { max-height:44px; margin-bottom:6px; }
.business-name { font-size:28px; font-weight:700; }
.doc-title { font-size:36px; font-weight:700; letter-spacing:1px; margin-bottom:22px; }
.doc-meta { font-size:11.5px; line-height:23px; color:rgba(255,255,255,.8); }
.doc-meta strong { color:#fff; }
.section-title { font-size:10px; font-weight:700; color:#666; letter-spacing:1px; margin-bottom:7px; text-transform:uppercase; }
.bill-text { font-size:12.5px; line-height:20px; }
.table-wrap { padding:18px 40px 0; }
.invoice-table { width:100%; border-collapse:collapse; }
.invoice-table thead th { background:{{ $dark }}; color:#fff; padding:10px 8px; font-size:9px; letter-spacing:1px; text-transform:uppercase; text-align:center; }
.invoice-table thead th:first-child { width:30px; }
.invoice-table thead th:nth-child(2) { text-align:left; }
.invoice-table thead th:nth-child(5) { text-align:right; }
.invoice-table tbody td { padding:10px 8px; border-bottom:1px solid #e6e6e6; vertical-align:top; font-size:11px; text-align:center; }
.invoice-table tbody td:nth-child(2) { text-align:left; }
.invoice-table tbody td:nth-child(5) { text-align:right; }
.invoice-table tbody td:nth-child(3), .invoice-table tbody td:nth-child(4) { text-align:right; }
.bottom-table { width:100%; border-collapse:collapse; margin-top:20px; }
.bottom-table td { vertical-align:top; padding:0 40px; }
.total-table { width:100%; border-collapse:collapse; }
.total-table td { padding:6px 0; font-size:11.5px; }
.total-table td:last-child { text-align:right; font-weight:600; }
.total-table .discount td { color:#dc2626; }
.grand-total td { border-top:2px solid {{ $dark }}; padding-top:10px; font-size:11px; font-weight:700; }
.grand-total td:last-child { color:{{ $accent }}; }
.notes-section { margin-top:16px; }
.notes-text { font-size:10px; color:#000; line-height:15px; margin-top:4px; }
.qr-wrap { text-align:right; margin-top:16px; }
.qr-box { width:90px; height:90px; border:1px solid #000; display:inline-block; text-align:center; line-height:90px; background:#fff; }
.qr-box img { vertical-align:middle; max-width:88px; max-height:88px; }
.qr-title { margin-top:4px; font-size:8px; font-weight:700; letter-spacing:1px; }
.signature-row { margin:24px 40px 0; border-top:1px solid #e8e8e8; padding-top:18px; }
.signature-table { width:100%; border-collapse:collapse; }
.signature-table td { vertical-align:bottom; }
.thank-you { font-size:28px; font-weight:300; color:{{ $accent }}; }
.signature-name { font-size:24px; font-family:'DejaVu Sans', cursive; margin-bottom:3px; }
.signature-role { font-size:10px; color:#666; }
.footer { margin:30px 40px 20px; padding-top:14px; border-top:1px solid #e8e8e8; font-size:9px; color:#666; }
.words-row td { padding-top:8px; font-size:10px; color:#555; font-style:italic; }
</style>
</head>
<body>
<table class="header-table"><tr>
<td class="company-cell">
@if ($quotation->business->logo)<img src="{{ storage_path('app/public/' . $quotation->business->logo) }}" alt="Logo" class="logo-img"><br>@endif
<div class="business-name">{{ $quotation->business->name }}</div>
@if ($quotation->business->address)<div style="font-size:10px;color:#666;margin-top:2px;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->address))) !!}</div>@endif
<br><div class="section-title">{{ __('QUOTE TO') }}</div>
<div class="bill-text" style="line-height:14px;">{{ $quotation->customer?->name ?? __('Walk-in Customer') }}<br>
@if ($quotation->customer?->email){{ $quotation->customer->email }}<br>@endif
@if ($quotation->customer?->phone){{ $quotation->customer->phone }}<br>@endif
@if ($quotation->customer?->address){{ $quotation->customer->address }}<br>@endif</div>
</td>
<td class="sidebar-cell">
<div class="doc-title">{{ strtoupper($title) }}</div>
<div class="doc-meta"><strong>{{ __('Quotation') }} #</strong> {{ $quotation->quotation_number }}<br>
<strong>{{ __('Issue Date') }}</strong> {{ $quotation->issue_date->format('d M Y') }}<br>
@if ($quotation->valid_until)<strong>{{ __('Valid Until') }}</strong> {{ $quotation->valid_until->format('d M Y') }}@endif</div>
</td>
</tr></table>

<div class="table-wrap">
<table class="invoice-table">
<thead><tr><th>#</th><th>{{ __('Item / Description') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Unit Price') }}</th><th>{{ __('Amount') }}</th></tr></thead>
<tbody>
@forelse ($quotation->items as $item)
<tr><td>{{ $loop->iteration }}</td><td><div style="font-weight:700;font-size:11.5px;">{{ $item->description ?: '—' }}</div></td><td>{{ number_format($item->quantity, 2) }}</td><td>{{ formatCurrency($item->unit_price) }}</td><td>{{ formatCurrency($item->total) }}</td></tr>
@empty
<tr><td colspan="5" style="text-align:center;padding:16px;color:#bfbfbf;">{{ __('No items.') }}</td></tr>
@endforelse
</tbody>
</table>
</div>

<table class="bottom-table"><tr>
<td style="width:55%;">
@if ($quotation->notes)
<div class="notes-section"><div class="section-title">{{ __('NOTES') }}</div><div class="notes-text">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->notes))) !!}</div></div>@endif
@if ($quotation->business->quotes_notes)
<div class="notes-section"><div class="section-title">{{ __('TERMS') }}</div><div class="notes-text"><p style="font-style:italic;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->quotes_notes))) !!}</p></div></div>@endif
</td>
<td style="width:45%;">
<table class="total-table">
<tr><td>{{ __('Sub Total') }}</td><td>{{ formatCurrency($subtotal) }}</td></tr>
@if (!$quotation->hide_total && (float) $discountAmount > 0 && $quotation->show_discount_column)<tr class="discount"><td>{{ __('Discount') }}</td><td>-{{ formatCurrency($discountAmount) }}</td></tr>@endif
@if ((float) $taxAmount > 0)<tr><td>{{ $quotation->tax_name ?? __('Tax') }} ({{ $quotation->tax_rate ?? 0 }}%)</td><td>{{ formatCurrency($taxAmount) }}</td></tr>@endif
@if ((float) $whtAmount > 0)<tr><td style="color:#d97706;">{{ __('WHT') }} ({{ $quotation->wht_rate ?? 0 }}%)</td><td style="color:#d97706;">-{{ formatCurrency($whtAmount) }}</td></tr>@endif
@if (!$quotation->hide_total)
<tr class="grand-total"><td>{{ __('GRAND TOTAL') }}</td><td>{{ formatCurrency($total) }}</td></tr>
@if ((float) $whtAmount > 0)<tr><td style="padding-top:4px;font-size:10px;">{{ __('Net Payable') }}</td><td style="padding-top:4px;font-size:10px;font-weight:700;color:#d97706;">{{ formatCurrency($total - $whtAmount) }}</td></tr>@endif
@if ($quotation->show_amount_in_words)<tr class="words-row"><td colspan="2">{{ \App\Helpers\AmountInWords::convert($total) }}</td></tr>@endif
@endif
</table>
@php $qrData = $quotation->business->name . "\n" . $quotation->quotation_number . "\n" . formatCurrency($total); @endphp
@if (class_exists(\App\Helpers\QrCode::class))
<div class="qr-wrap"><div class="qr-box"><img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 200) }}" alt="QR"></div><div class="qr-title">{{ __('SCAN TO VERIFY') }}</div></div>@endif
</td>
</tr></table>

<div class="signature-row">
<table class="signature-table"><tr>
<td><div class="thank-you">{{ __('Thank You!') }}</div></td>
<td class="signature-wrap" style="text-align:right;"><div class="signature-name">{{ $quotation->business->name }}</div><div class="signature-role">{{ __('Authorized Signature') }}</div></td>
</tr></table>
</div>

<div class="footer"><table style="width:100%;"><tr>
<td>{{ $quotation->business->name }}</td>
<td style="text-align:center;">@if ($quotation->business->email){{ $quotation->business->email }}@endif</td>
<td style="text-align:right;">{{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</td>
</tr></table></div>
</body></html>
