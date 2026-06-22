@php
$accent = $invoice->business->accent_color ?? '#f97316';
$hex = ltrim($accent, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
$dark = sprintf('#%02x%02x%02x', (int)($r * 0.55), (int)($g * 0.55), (int)($b * 0.55));
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
body { font-family: 'DejaVu Sans', Arial, sans-serif; color:#222; background:#fff; font-size:11px; line-height:1.35; }
.page { padding:28px 32px; }
.top-row { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid {{ $accent }}; padding-bottom:14px; margin-bottom:14px; }
.logo-img { max-height:38px; margin-bottom:4px; }
.biz-name { font-size:20px; font-weight:700; }
.biz-info { font-size:9px; color:#666; line-height:15px; }
.doc-label { font-size:32px; font-weight:700; color:{{ $accent }}; letter-spacing:1px; }
.doc-meta { text-align:right; font-size:10px; line-height:18px; color:#555; }
.doc-meta strong { color:#222; }
.status-tag { display:inline-block; margin-top:4px; padding:1px 10px; font-size:8px; font-weight:700; border-radius:2px; text-transform:uppercase; letter-spacing:1px; }
.status-paid { background:#dcfce7; color:#166534; }
.status-unpaid { background:#fee2e2; color:#991b1b; }
.status-partial { background:#fef3c7; color:#92400e; }
.status-overdue { background:#fce7f3; color:#9d174d; }
.addr-row { display:flex; justify-content:space-between; margin-bottom:14px; }
.addr-box { width:48%; }
.addr-label { font-size:8px; font-weight:700; color:#999; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:3px; }
.addr-name { font-size:13px; font-weight:600; }
.addr-text { font-size:10px; color:#555; line-height:15px; }
.inv-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
.inv-table thead th { border-bottom:2px solid {{ $accent }}; color:{{ $accent }}; padding:8px 6px; font-size:8px; font-weight:700; letter-spacing:1px; text-transform:uppercase; text-align:center; }
.inv-table thead th:first-child { width:24px; text-align:center; }
.inv-table thead th:nth-child(2) { text-align:left; }
.inv-table thead th:last-child { text-align:right; }
.inv-table tbody td { padding:8px 6px; border-bottom:1px solid #eee; vertical-align:top; font-size:10px; text-align:center; }
.inv-table tbody td:nth-child(2) { text-align:left; }
.inv-table tbody td:last-child { text-align:right; }
.inv-table tbody td:nth-child(3),
.inv-table tbody td:nth-child(4) { text-align:right; }
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
.pay-mini { margin-top:10px; }
.pay-mini table { width:100%; font-size:9px; border-collapse:collapse; }
.pay-mini td { padding:1px 0; }
.pay-mini td:last-child { text-align:right; }
.pay-mini .div td { border-top:1px solid #ddd; padding-top:4px; }
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
@if ($invoice->business->logo)<img src="{{ storage_path('app/public/' . $invoice->business->logo) }}" alt="Logo" class="logo-img">@endif
<div class="biz-name">{{ $invoice->business->name }}</div>
<div class="biz-info">@if ($invoice->business->email){{ $invoice->business->email }}<br>@endif
@if ($invoice->business->phone){{ $invoice->business->phone }}@endif</div>
</div>
<div style="text-align:right;">
<div class="doc-label">{{ strtoupper($title) }}</div>
<div class="doc-meta">
<strong>{{ __('Number') }}:</strong> {{ $invoice->invoice_number }}<br>
<strong>{{ __('Issue Date') }}:</strong> {{ $invoice->issue_date->format('d M Y') }}<br>
@if ($invoice->due_date)<strong>{{ __('Due Date') }}:</strong> {{ $invoice->due_date->format('d M Y') }}<br>@endif
<span class="status-tag status-{{ $status }}">{{ __(ucfirst($status)) }}</span>
</div>
</div>
</div>

<div class="addr-row">
<div class="addr-box">
<div class="addr-label">{{ __('BILL TO') }}</div>
<div class="addr-name">{{ $invoice->customer?->name ?? __('Walk-in Customer') }}</div>
<div class="addr-text">@if ($invoice->customer?->email){{ $invoice->customer->email }}<br>@endif
@if ($invoice->customer?->phone){{ $invoice->customer->phone }}<br>@endif
@if ($invoice->customer?->address){{ $invoice->customer->address }}@endif</div>
</div>
<div class="addr-box">
<div class="addr-label">{{ __('FROM') }}</div>
<div class="addr-text">{{ $invoice->business->name }}<br>
@if ($invoice->business->address){!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->address))) !!}@endif</div>
</div>
</div>

<table class="inv-table">
<thead><tr><th>#</th><th>{{ __('Item / Description') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Unit Price') }}</th><th>{{ __('Amount') }}</th></tr></thead>
<tbody>
@forelse ($invoice->items as $item)
<tr><td>{{ $loop->iteration }}</td><td><div style="font-weight:600;">{{ $item->description ?: '—' }}</div></td><td>{{ number_format($item->quantity, 2) }}</td><td>{{ formatCurrency($item->unit_price) }}</td><td>{{ formatCurrency($item->total) }}</td></tr>
@empty
<tr><td colspan="5" style="text-align:center;padding:16px;color:#bfbfbf;">{{ __('No items.') }}</td></tr>
@endforelse
</tbody>
</table>

<div class="bottom-row">
<div class="bottom-left">
@php $payments = $invoice->payments()->orderBy('created_at', 'desc')->get(); $balance = max(0, (float) $invoice->total - $paid); @endphp
<div class="addr-label">{{ __('PAYMENTS') }}</div>
@if ($payments->isNotEmpty())
<div class="pay-mini"><table>
@foreach ($payments as $p)
<tr><td style="font-family:monospace;">{{ $p->receipt_number }}</td><td style="color:#999;">{{ $p->created_at->format('d M Y') }}</td><td>{{ formatCurrency($p->amount) }}</td></tr>
@endforeach
<tr class="div"><td colspan="3"></td></tr>
<tr><td colspan="2">{{ __('Total') }}</td><td>{{ formatCurrency($invoice->total) }}</td></tr>
<tr><td colspan="2">{{ __('Paid') }}</td><td>{{ formatCurrency($paid) }}</td></tr>
<tr style="font-weight:600;color:#dc2626;"><td colspan="2">{{ __('Balance Due') }}</td><td>{{ formatCurrency($balance) }}</td></tr>
</table></div>
@else<p style="font-size:9px;color:#bfbfbf;">{{ __('No payments recorded yet.') }}</p>@endif
@if ($invoice->notes || $invoice->business->invoice_notes)
<div class="notes-mini"><strong style="font-size:8px;letter-spacing:1px;">{{ __('NOTES') }}</strong><br>
@if ($invoice->notes){!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->notes))) !!}<br>@endif
@if ($invoice->business->invoice_notes)<em>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->invoice_notes))) !!}</em>@endif
</div>@endif
</div>
<div class="bottom-right">
<table class="totals-table">
<tr><td>{{ __('Sub Total') }}</td><td>{{ formatCurrency($subtotal) }}</td></tr>
@if (!$invoice->hide_total && (float) $discountAmount > 0 && $invoice->show_discount_column)<tr class="disc-row"><td>{{ __('Discount') }}</td><td>-{{ formatCurrency($discountAmount) }}</td></tr>@endif
@if ((float) $taxAmount > 0)<tr><td>{{ $invoice->tax_name ?? __('Tax') }} ({{ $invoice->tax_rate ?? 0 }}%)</td><td>{{ formatCurrency($taxAmount) }}</td></tr>@endif
@if ((float) $whtAmount > 0)<tr><td style="color:#d97706;">{{ __('WHT') }} ({{ $invoice->wht_rate ?? 0 }}%)</td><td style="color:#d97706;">-{{ formatCurrency($whtAmount) }}</td></tr>@endif
@if (!$invoice->hide_total)
<tr class="grand-row"><td>{{ __('GRAND TOTAL') }}</td><td>{{ formatCurrency($total) }}</td></tr>
@if ((float) $whtAmount > 0)<tr><td style="padding-top:4px;font-size:10px;font-weight:700;color:#d97706;border-top:1px solid #d97706;">{{ __('Net Payable') }}</td><td style="padding-top:4px;font-size:10px;font-weight:700;color:#d97706;border-top:1px solid #d97706;">{{ formatCurrency($total - $whtAmount) }}</td></tr>@endif
@if ($invoice->show_amount_in_words)<tr class="words-row"><td colspan="2">{{ \App\Helpers\AmountInWords::convert($total) }}</td></tr>@endif
@endif
</table>
@php $qrData = $invoice->business->name . "\n" . $invoice->invoice_number . "\n" . formatCurrency($total); @endphp
@if (class_exists(\App\Helpers\QrCode::class))
<div class="qr-mini"><img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 150) }}" alt="QR"><br><span style="font-size:7px;letter-spacing:1px;">{{ __('SCAN TO PAY') }}</span></div>@endif
</div>
</div>

<div class="footer">{{ $invoice->business->name }} &bull; {{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
</div>
</body></html>
