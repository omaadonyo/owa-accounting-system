@php
$accent = $quotation->business->accent_color ?? '#f97316';
$hex = ltrim($accent, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
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
body { font-family: 'DejaVu Sans', Arial, sans-serif; color:#333; font-size:12px; line-height:1.4; }
.top-bar { background:{{ $accent }}; padding:18px 40px; color:#fff; }
.top-bar .doc-label { font-size:28px; font-weight:700; letter-spacing:2px; }
.top-bar .doc-number { font-size:14px; opacity:.85; margin-top:2px; }
.header-flex { display:flex; justify-content:space-between; padding:30px 40px 0; }
.header-flex .left { width:55%; }
.header-flex .right { width:40%; text-align:right; }
.logo-img { max-height:44px; margin-bottom:8px; }
.biz-name { font-size:22px; font-weight:700; }
.biz-details { font-size:10px; color:#666; margin-top:3px; line-height:16px; }
.section-label { font-size:9px; font-weight:700; color:#999; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:4px; }
.customer-name { font-size:15px; font-weight:600; }
.customer-details { font-size:11px; color:#555; line-height:16px; margin-top:2px; }
.table-wrap { padding:20px 40px 0; }
.inv-table { width:100%; border-collapse:collapse; border-radius:8px; overflow:hidden; }
.inv-table thead th { background:{{ $accent }}; color:#fff; padding:12px 10px; font-size:9px; letter-spacing:1px; text-transform:uppercase; text-align:center; }
.inv-table thead th:nth-child(2) { text-align:left; }
.inv-table thead th:last-child { text-align:right; }
.inv-table tbody td { padding:12px 10px; border-bottom:1px solid #eee; vertical-align:top; font-size:11px; text-align:center; }
.inv-table tbody td:nth-child(2) { text-align:left; }
.inv-table tbody td:last-child { text-align:right; }
.inv-table tbody tr:nth-child(even) td { background:rgba({{ $r }},{{ $g }},{{ $b }},0.06); }
.bottom-section { padding:20px 40px 0; display:flex; gap:40px; }
.bottom-section .left-col { width:55%; }
.bottom-section .right-col { width:45%; }
.total-box { background:rgba({{ $r }},{{ $g }},{{ $b }},0.08); border-radius:8px; padding:20px; }
.total-row { display:flex; justify-content:space-between; padding:5px 0; font-size:11px; }
.total-row.discount { color:#dc2626; }
.grand-row { border-top:2px solid {{ $accent }}; padding-top:10px; margin-top:5px; font-weight:700; font-size:13px; }
.grand-row .amount { color:{{ $accent }}; }
.words-row { font-size:10px; color:#666; font-style:italic; padding-top:6px; }
.notes-section { margin-top:14px; }
.notes-text { font-size:10px; color:#333; line-height:15px; margin-top:4px; }
.qr-wrap { text-align:right; margin-top:14px; }
.qr-box { width:80px; height:80px; border:1px solid #ddd; display:inline-block; text-align:center; line-height:80px; background:#fff; border-radius:4px; }
.qr-box img { vertical-align:middle; max-width:76px; max-height:76px; }
.qr-title { font-size:8px; font-weight:700; letter-spacing:1px; margin-top:3px; }
.footer { margin:24px 40px 14px; padding-top:12px; border-top:1px solid #ddd; font-size:9px; color:#999; text-align:center; }
</style>
</head>
<body>
<div class="top-bar"><div class="doc-label">{{ strtoupper($title) }}</div><div class="doc-number">{{ $quotation->quotation_number }}</div></div>
<div class="header-flex">
<div class="left">
@if ($quotation->business->logo)<img src="{{ storage_path('app/public/' . $quotation->business->logo) }}" alt="Logo" class="logo-img">@endif
<div class="biz-name">{{ $quotation->business->name }}</div>
<div class="biz-details">@if ($quotation->business->address){!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->address))) !!}<br>@endif
@if ($quotation->business->email){{ $quotation->business->email }}@endif</div>
</div>
<div class="right">
<div class="section-label">{{ __('DATES') }}</div>
<div style="font-size:12px;"><strong>{{ __('Issue') }}:</strong> {{ $quotation->issue_date->format('d M Y') }}</div>
@if ($quotation->valid_until)<div style="font-size:12px;"><strong>{{ __('Valid Until') }}:</strong> {{ $quotation->valid_until->format('d M Y') }}</div>@endif
</div>
</div>

<div class="header-flex" style="padding-top:14px;">
<div class="left">
<div class="section-label">{{ __('QUOTE TO') }}</div>
<div class="customer-name">{{ $quotation->customer?->name ?? __('Walk-in Customer') }}</div>
<div class="customer-details">@if ($quotation->customer?->email){{ $quotation->customer->email }}<br>@endif
@if ($quotation->customer?->phone){{ $quotation->customer->phone }}<br>@endif
@if ($quotation->customer?->address){{ $quotation->customer->address }}@endif</div>
</div>
</div>

<div class="table-wrap">
<table class="inv-table">
<thead><tr><th>#</th><th>{{ __('Item / Description') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Unit Price') }}</th><th>{{ __('Amount') }}</th></tr></thead>
<tbody>
@forelse ($quotation->items as $item)
<tr><td>{{ $loop->iteration }}</td><td><div style="font-weight:600;">{{ $item->description ?: '—' }}</div></td><td>{{ number_format($item->quantity, 2) }}</td><td>{{ formatCurrency($item->unit_price) }}</td><td>{{ formatCurrency($item->total) }}</td></tr>
@empty
<tr><td colspan="5" style="text-align:center;padding:20px;color:#bfbfbf;">{{ __('No items.') }}</td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="bottom-section">
<div class="left-col">
@if ($quotation->notes)
<div class="notes-section"><div class="section-label">{{ __('NOTES') }}</div><div class="notes-text">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->notes))) !!}</div></div>@endif
@if ($quotation->business->quotes_notes)
<div class="notes-section"><div class="section-label">{{ __('TERMS') }}</div><div class="notes-text"><p style="font-style:italic;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->quotes_notes))) !!}</p></div></div>@endif
</div>
<div class="right-col">
<div class="total-box">
<div class="total-row"><span>{{ __('Sub Total') }}</span><span>{{ formatCurrency($subtotal) }}</span></div>
@if (!$quotation->hide_total && (float) $discountAmount > 0 && $quotation->show_discount_column)<div class="total-row discount"><span>{{ __('Discount') }}</span><span>-{{ formatCurrency($discountAmount) }}</span></div>@endif
@if ((float) $taxAmount > 0)<div class="total-row"><span>{{ $quotation->tax_name ?? __('Tax') }} ({{ $quotation->tax_rate ?? 0 }}%)</span><span>{{ formatCurrency($taxAmount) }}</span></div>@endif
@if ((float) $whtAmount > 0)<div class="total-row" style="color:#d97706;"><span>{{ __('WHT') }} ({{ $quotation->wht_rate ?? 0 }}%)</span><span>-{{ formatCurrency($whtAmount) }}</span></div>@endif
@if (!$quotation->hide_total)
<div class="total-row grand-row"><span>{{ __('GRAND TOTAL') }}</span><span class="amount">{{ formatCurrency($total) }}</span></div>
@if ((float) $whtAmount > 0)<div class="total-row" style="padding-top:4px;font-size:10px;font-weight:700;color:#d97706;border-top:1px solid #d97706;"><span>{{ __('Net Payable') }}</span><span>{{ formatCurrency($total - $whtAmount) }}</span></div>@endif
@if ($quotation->show_amount_in_words)<div class="words-row">{{ \App\Helpers\AmountInWords::convert($total) }}</div>@endif
@endif
</div>
@php $qrData = $quotation->business->name . "\n" . $quotation->quotation_number . "\n" . formatCurrency($total); @endphp
@if (class_exists(\App\Helpers\QrCode::class))
<div class="qr-wrap"><div class="qr-box"><img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 180) }}" alt="QR"></div><div class="qr-title">{{ __('SCAN TO VERIFY') }}</div></div>@endif
</div>
</div>

<div class="footer">{{ $quotation->business->name }} &bull; @if ($quotation->business->email){{ $quotation->business->email }} &bull; @endif {{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
</body></html>
