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
body { font-family: 'DejaVu Sans', Arial, sans-serif; color:#222; font-size:11px; line-height:1.35; }
.page { padding:28px 32px; }
.top-row { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid {{ $accent }}; padding-bottom:14px; margin-bottom:14px; }
.logo-img { max-height:38px; margin-bottom:4px; }
.biz-name { font-size:20px; font-weight:700; }
.biz-info { font-size:9px; color:#666; line-height:15px; }
.doc-label { font-size:32px; font-weight:700; color:{{ $accent }}; letter-spacing:1px; }
.doc-meta { text-align:right; font-size:10px; line-height:18px; color:#555; }
.doc-meta strong { color:#222; }
.addr-row { display:flex; justify-content:space-between; margin-bottom:14px; }
.addr-box { width:48%; }
.addr-label { font-size:8px; font-weight:700; color:#999; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:3px; }
.addr-name { font-size:13px; font-weight:600; }
.addr-text { font-size:10px; color:#555; line-height:15px; }
.inv-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
.inv-table thead th { border-bottom:2px solid {{ $accent }}; color:{{ $accent }}; padding:8px 6px; font-size:8px; font-weight:700; letter-spacing:1px; text-transform:uppercase; text-align:center; }
.inv-table thead th:first-child { width:24px; }
.inv-table thead th:nth-child(2) { text-align:left; }
.inv-table thead th:last-child { text-align:right; }
.inv-table tbody td { padding:8px 6px; border-bottom:1px solid #eee; vertical-align:top; font-size:10px; text-align:center; }
.inv-table tbody td:nth-child(2) { text-align:left; }
.inv-table tbody td:last-child { text-align:right; }
.inv-table tbody td:nth-child(3), .inv-table tbody td:nth-child(4) { text-align:right; }
.bottom-row { display:flex; justify-content:space-between; gap:20px; }
.bottom-left { width:55%; }
.bottom-right { width:45%; }
.totals-table { width:100%; border-collapse:collapse; }
.totals-table td { padding:4px 0; font-size:11px; }
.totals-table td:last-child { text-align:right; font-weight:600; }
.totals-table .disc-row td { color:#dc2626; }
.totals-table .grand-row td { border-top:2px solid {{ $accent }}; padding-top:8px; font-weight:700; font-size:12px; }
.totals-table .grand-row td:last-child { color:{{ $accent }}; }
.words-row td { font-size:9px; color:#666; font-style:italic; padding-top:4px; }
.notes-mini { margin-top:10px; font-size:9px; color:#444; line-height:14px; }
.qr-mini { text-align:right; margin-top:10px; }
.qr-mini img { width:70px; height:70px; border:1px solid #ddd; }
.footer { margin-top:18px; padding-top:10px; border-top:1px solid #ddd; font-size:8px; color:#999; text-align:center; }
</style>
</head>
<body>
<div class="page">
<div class="top-row">
<div>
@if ($quotation->business->logo)<img src="{{ storage_path('app/public/' . $quotation->business->logo) }}" alt="Logo" class="logo-img">@endif
<div class="biz-name">{{ $quotation->business->name }}</div>
<div class="biz-info">@if ($quotation->business->email){{ $quotation->business->email }}<br>@endif
@if ($quotation->business->phone){{ $quotation->business->phone }}@endif</div>
</div>
<div style="text-align:right;">
<div class="doc-label">{{ strtoupper($title) }}</div>
<div class="doc-meta">
<strong>{{ __('Number') }}:</strong> {{ $quotation->quotation_number }}<br>
<strong>{{ __('Issue Date') }}:</strong> {{ $quotation->issue_date->format('d M Y') }}<br>
@if ($quotation->valid_until)<strong>{{ __('Valid Until') }}:</strong> {{ $quotation->valid_until->format('d M Y') }}<br>@endif
</div>
</div>
</div>

<div class="addr-row">
<div class="addr-box">
<div class="addr-label">{{ __('QUOTE TO') }}</div>
<div class="addr-name">{{ $quotation->customer?->name ?? __('Walk-in Customer') }}</div>
<div class="addr-text">@if ($quotation->customer?->email){{ $quotation->customer->email }}<br>@endif
@if ($quotation->customer?->phone){{ $quotation->customer->phone }}<br>@endif
@if ($quotation->customer?->address){{ $quotation->customer->address }}@endif</div>
</div>
<div class="addr-box">
<div class="addr-label">{{ __('FROM') }}</div>
<div class="addr-text">{{ $quotation->business->name }}<br>
@if ($quotation->business->address){!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->address))) !!}@endif</div>
</div>
</div>

<table class="inv-table">
<thead><tr><th>#</th><th>{{ __('Item / Description') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Unit Price') }}</th><th>{{ __('Amount') }}</th></tr></thead>
<tbody>
@forelse ($quotation->items as $item)
<tr><td>{{ $loop->iteration }}</td><td><div style="font-weight:600;">{{ $item->description ?: '—' }}</div></td><td>{{ number_format($item->quantity, 2) }}</td><td>{{ formatCurrency($item->unit_price) }}</td><td>{{ formatCurrency($item->total) }}</td></tr>
@empty
<tr><td colspan="5" style="text-align:center;padding:16px;color:#bfbfbf;">{{ __('No items.') }}</td></tr>
@endforelse
</tbody>
</table>

<div class="bottom-row">
<div class="bottom-left">
@if ($quotation->notes)
<div class="notes-mini"><strong style="font-size:8px;letter-spacing:1px;">{{ __('NOTES') }}</strong><br>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->notes))) !!}</div>@endif
@if ($quotation->business->quotes_notes)
<div class="notes-mini"><strong style="font-size:8px;letter-spacing:1px;">{{ __('TERMS') }}</strong><br><em>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->quotes_notes))) !!}</em></div>@endif
</div>
<div class="bottom-right">
<table class="totals-table">
<tr><td>{{ __('Sub Total') }}</td><td>{{ formatCurrency($subtotal) }}</td></tr>
@if (!$quotation->hide_total && (float) $discountAmount > 0 && $quotation->show_discount_column)<tr class="disc-row"><td>{{ __('Discount') }}</td><td>-{{ formatCurrency($discountAmount) }}</td></tr>@endif
@if ((float) $taxAmount > 0)<tr><td>{{ $quotation->tax_name ?? __('Tax') }} ({{ $quotation->tax_rate ?? 0 }}%)</td><td>{{ formatCurrency($taxAmount) }}</td></tr>@endif
@if ((float) $whtAmount > 0)<tr><td style="color:#d97706;">{{ __('WHT') }} ({{ $quotation->wht_rate ?? 0 }}%)</td><td style="color:#d97706;">-{{ formatCurrency($whtAmount) }}</td></tr>@endif
@if (!$quotation->hide_total)
<tr class="grand-row"><td>{{ __('GRAND TOTAL') }}</td><td>{{ formatCurrency($total) }}</td></tr>
@if ((float) $whtAmount > 0)<tr><td style="padding-top:4px;font-size:10px;font-weight:700;color:#d97706;border-top:1px solid #d97706;">{{ __('Net Payable') }}</td><td style="padding-top:4px;font-size:10px;font-weight:700;color:#d97706;border-top:1px solid #d97706;">{{ formatCurrency($total - $whtAmount) }}</td></tr>@endif
@if ($quotation->show_amount_in_words)<tr class="words-row"><td colspan="2">{{ \App\Helpers\AmountInWords::convert($total) }}</td></tr>@endif
@endif
</table>
@php $qrData = $quotation->business->name . "\n" . $quotation->quotation_number . "\n" . formatCurrency($total); @endphp
@if (class_exists(\App\Helpers\QrCode::class))
<div class="qr-mini"><img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 150) }}" alt="QR"><br><span style="font-size:7px;letter-spacing:1px;">{{ __('SCAN TO VERIFY') }}</span></div>@endif
</div>
</div>

<div class="footer">{{ $quotation->business->name }} &bull; {{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
</div>
</body></html>
