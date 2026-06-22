@php
$accent = $invoice->business->accent_color ?? '#f97316';
$hex = ltrim($accent, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
$dark = sprintf('#%02x%02x%02x', (int)($r * 0.55), (int)($g * 0.55), (int)($b * 0.55));
$light = sprintf('rgba(%d,%d,%d,0.08)', $r, $g, $b);
$status = 'unpaid';
$paid = (float) $invoice->paid_amount;
if ($paid >= (float) $invoice->total) { $status = 'paid'; } elseif ($paid > 0) { $status = 'partial'; }
if ($invoice->due_date && now()->gt($invoice->due_date) && $status !== 'paid') { $status = 'overdue'; }
$title = $invoice->custom_title ?: __('INVOICE');
$subtotal = $invoice->subtotal;
$taxAmount = $invoice->tax_amount;
$discountAmount = $invoice->discount_amount;
$whtAmount = $invoice->wht_amount;
$total = $invoice->total;
if ($invoice->tax_inclusive && $invoice->tax_rate > 0) { $taxAmount = round(($invoice->subtotal - $discountAmount) * ($invoice->tax_rate / 100), 2); $total = round($invoice->subtotal - $discountAmount + $taxAmount, 2); } else { $taxAmount = 0; $total = round($invoice->subtotal - $discountAmount, 2); }
@endphp
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{{ $title }} - {{ $invoice->invoice_number }}</title>
<style>
@page { size: A4; margin: 0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'DejaVu Sans', Arial, sans-serif; color:#333; background:#fff; font-size:12px; line-height:1.4; }
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
.status-pill { display:inline-block; margin-top:6px; padding:3px 14px; font-size:9px; font-weight:700; border-radius:12px; text-transform:uppercase; letter-spacing:1px; }
.status-paid { background:#dcfce7; color:#166534; }
.status-unpaid { background:#fee2e2; color:#991b1b; }
.status-partial { background:#fef3c7; color:#92400e; }
.status-overdue { background:#fce7f3; color:#9d174d; }
.table-wrap { padding:20px 40px 0; }
.inv-table { width:100%; border-collapse:collapse; border-radius:8px; overflow:hidden; }
.inv-table thead th { background:{{ $accent }}; color:#fff; padding:12px 10px; font-size:9px; letter-spacing:1px; text-transform:uppercase; text-align:center; }
.inv-table thead th:nth-child(2) { text-align:left; }
.inv-table thead th:last-child { text-align:right; }
.inv-table tbody td { padding:12px 10px; border-bottom:1px solid #eee; vertical-align:top; font-size:11px; text-align:center; }
.inv-table tbody td:nth-child(2) { text-align:left; }
.inv-table tbody td:last-child { text-align:right; }
.inv-table tbody tr:last-child td { border-bottom:none; }
.inv-table tbody tr:nth-child(even) td { background:{{ $light }}; }
.bottom-section { padding:20px 40px 0; display:flex; gap:40px; }
.bottom-section .left-col { width:55%; }
.bottom-section .right-col { width:45%; }
.total-box { background:{{ $light }}; border-radius:8px; padding:20px; }
.total-row { display:flex; justify-content:space-between; padding:5px 0; font-size:11px; }
.total-row.discount { color:#dc2626; }
.grand-row { border-top:2px solid {{ $accent }}; padding-top:10px; margin-top:5px; font-weight:700; font-size:13px; }
.grand-row .amount { color:{{ $accent }}; }
.words-row { font-size:10px; color:#666; font-style:italic; padding-top:6px; }
.payment-history { margin-top:14px; }
.pay-table { width:100%; font-size:10px; border-collapse:collapse; }
.pay-table td { padding:2px 0; }
.pay-table td:last-child { text-align:right; }
.pay-divider td { border-top:1px solid #ddd; padding-top:5px; }
.pay-balance td { font-weight:600; }
.pay-due td { color:#dc2626; }
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
<div class="top-bar"><div class="doc-label">{{ strtoupper($title) }}</div><div class="doc-number">{{ $invoice->invoice_number }}</div></div>
<div class="header-flex">
<div class="left">
@if ($invoice->business->logo)<img src="{{ storage_path('app/public/' . $invoice->business->logo) }}" alt="Logo" class="logo-img">@endif
<div class="biz-name">{{ $invoice->business->name }}</div>
<div class="biz-details">@if ($invoice->business->address){!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->address))) !!}<br>@endif
@if ($invoice->business->email){{ $invoice->business->email }}@endif</div>
</div>
<div class="right">
<div class="section-label">{{ __('DATES') }}</div>
<div style="font-size:12px;"><strong>{{ __('Issue') }}:</strong> {{ $invoice->issue_date->format('d M Y') }}</div>
@if ($invoice->due_date)<div style="font-size:12px;"><strong>{{ __('Due') }}:</strong> {{ $invoice->due_date->format('d M Y') }}</div>@endif
<span class="status-pill status-{{ $status }}">{{ __(ucfirst($status)) }}</span>
</div>
</div>

<div class="header-flex" style="padding-top:14px;">
<div class="left">
<div class="section-label">{{ __('INVOICE TO') }}</div>
<div class="customer-name">{{ $invoice->customer?->name ?? __('Walk-in Customer') }}</div>
<div class="customer-details">@if ($invoice->customer?->email){{ $invoice->customer->email }}<br>@endif
@if ($invoice->customer?->phone){{ $invoice->customer->phone }}<br>@endif
@if ($invoice->customer?->address){{ $invoice->customer->address }}@endif</div>
</div>
</div>

<div class="table-wrap">
<table class="inv-table">
<thead><tr><th>#</th><th>{{ __('Item / Description') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Unit Price') }}</th><th>{{ __('Amount') }}</th></tr></thead>
<tbody>
@forelse ($invoice->items as $item)
<tr><td>{{ $loop->iteration }}</td><td><div style="font-weight:600;">{{ $item->description ?: '—' }}</div></td><td>{{ number_format($item->quantity, 2) }}</td><td>{{ formatCurrency($item->unit_price) }}</td><td>{{ formatCurrency($item->total) }}</td></tr>
@empty
<tr><td colspan="5" style="text-align:center;padding:20px;color:#bfbfbf;">{{ __('No items.') }}</td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="bottom-section">
<div class="left-col">
@php $payments = $invoice->payments()->orderBy('created_at', 'desc')->get(); $balance = max(0, (float) $invoice->total - $paid); @endphp
<div class="section-label">{{ __('PAYMENT HISTORY') }}</div>
@if ($payments->isNotEmpty())
<table class="pay-table">
@foreach ($payments as $p)
<tr><td style="font-family:monospace;">{{ $p->receipt_number }}</td><td style="color:#999;">{{ $p->created_at->format('d M Y') }}</td><td>{{ formatCurrency($p->amount) }}</td></tr>
@endforeach
<tr class="pay-divider"><td colspan="3"></td></tr>
<tr class="pay-balance"><td colspan="2">{{ __('Total') }}</td><td>{{ formatCurrency($invoice->total) }}</td></tr>
<tr class="pay-balance"><td colspan="2">{{ __('Paid') }}</td><td>{{ formatCurrency($paid) }}</td></tr>
<tr class="pay-balance pay-due"><td colspan="2">{{ __('Balance Due') }}</td><td>{{ formatCurrency($balance) }}</td></tr>
</table>
@else<p style="font-size:10px;color:#bfbfbf;margin-top:4px;">{{ __('No payments recorded yet.') }}</p>@endif
@if ($invoice->notes || $invoice->business->invoice_notes)
<div class="notes-section"><div class="section-label">{{ __('NOTES') }}</div><div class="notes-text">
@if ($invoice->notes){!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->notes))) !!}@endif
@if ($invoice->business->invoice_notes)<p style="font-style:italic;margin-top:4px;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->invoice_notes))) !!}</p>@endif
</div></div>@endif
</div>
<div class="right-col">
<div class="total-box">
<div class="total-row"><span>{{ __('Sub Total') }}</span><span>{{ formatCurrency($subtotal) }}</span></div>
@if (!$invoice->hide_total && (float) $discountAmount > 0 && $invoice->show_discount_column)<div class="total-row discount"><span>{{ __('Discount') }}</span><span>-{{ formatCurrency($discountAmount) }}</span></div>@endif
@if ((float) $taxAmount > 0)<div class="total-row"><span>{{ $invoice->tax_name ?? __('Tax') }} ({{ $invoice->tax_rate ?? 0 }}%)</span><span>{{ formatCurrency($taxAmount) }}</span></div>@endif
@if ((float) $whtAmount > 0)<div class="total-row" style="color:#d97706;"><span>{{ __('WHT') }} ({{ $invoice->wht_rate ?? 0 }}%)</span><span>-{{ formatCurrency($whtAmount) }}</span></div>@endif
@if (!$invoice->hide_total)
<div class="total-row grand-row"><span>{{ __('GRAND TOTAL') }}</span><span class="amount">{{ formatCurrency($total) }}</span></div>
@if ((float) $whtAmount > 0)<div class="total-row" style="padding-top:4px;font-size:10px;font-weight:700;color:#d97706;border-top:1px solid #d97706;"><span>{{ __('Net Payable') }}</span><span>{{ formatCurrency($total - $whtAmount) }}</span></div>@endif
@if ($invoice->show_amount_in_words)<div class="words-row">{{ \App\Helpers\AmountInWords::convert($total) }}</div>@endif
@endif
</div>
@php $qrData = $invoice->business->name . "\n" . $invoice->invoice_number . "\n" . formatCurrency($total); @endphp
@if (class_exists(\App\Helpers\QrCode::class))
<div class="qr-wrap"><div class="qr-box"><img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 180) }}" alt="QR"></div><div class="qr-title">{{ __('SCAN TO PAY') }}</div></div>@endif
</div>
</div>

<div class="footer">{{ $invoice->business->name }} &bull; @if ($invoice->business->email){{ $invoice->business->email }} &bull; @endif {{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
</body></html>
