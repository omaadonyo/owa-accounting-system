@php
$accent = $payment->invoice->business->accent_color ?? '#f97316';
$hex = ltrim($accent, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
$dark = sprintf('#%02x%02x%02x', (int)($r * 0.55), (int)($g * 0.55), (int)($b * 0.55));
@endphp
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{{ __('RECEIPT') }} - {{ $payment->receipt_number }}</title>
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
.detail-table { width:100%; border-collapse:collapse; }
.detail-table td { padding:8px 0; border-bottom:1px solid #eee; font-size:11px; }
.detail-table td:last-child { text-align:right; font-weight:600; }
.grand-row td { border-top:2px solid {{ $accent }}; padding-top:10px; font-size:13px; font-weight:700; }
.grand-row td:last-child { color:{{ $accent }}; }
.notes-section { margin-top:16px; }
.notes-text { font-size:10px; color:#333; line-height:15px; margin-top:4px; }
.footer { margin:30px 40px 20px; padding-top:14px; border-top:1px solid #e8e8e8; font-size:9px; color:#666; text-align:center; }
.thank-you { margin-top:20px; font-size:28px; font-weight:300; color:{{ $accent }}; text-align:center; }
</style>
</head>
<body>
<table class="header-table"><tr>
<td class="company-cell">
@if ($payment->invoice->business->logo)<img src="{{ storage_path('app/public/' . $payment->invoice->business->logo) }}" alt="Logo" class="logo-img"><br>@endif
<div class="business-name">{{ $payment->invoice->business->name }}</div>
@if ($payment->invoice->business->address)<div style="font-size:10px;color:#666;margin-top:2px;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $payment->invoice->business->address))) !!}</div>@endif
</td>
<td class="sidebar-cell">
<div class="doc-title">{{ __('RECEIPT') }}</div>
<div class="doc-meta"><strong>{{ __('Receipt') }} #</strong> {{ $payment->receipt_number }}<br>
<strong>{{ __('Date') }}</strong> {{ $payment->payment_date->format('d M Y') }}<br>
<strong>{{ __('Invoice') }}</strong> {{ $payment->invoice->invoice_number }}</div>
</td>
</tr></table>

<div class="section-title" style="padding:0 40px;">{{ __('RECEIVED FROM') }}</div>
<div class="bill-text" style="padding:4px 40px 0;">{{ $payment->invoice->customer?->name ?? __('Walk-in Customer') }}<br>
@if ($payment->invoice->customer?->email){{ $payment->invoice->customer->email }}<br>@endif
@if ($payment->invoice->customer?->phone){{ $payment->invoice->customer->phone }}@endif</div>

<div class="table-wrap">
<table class="detail-table">
<tr><td>{{ __('Invoice Number') }}</td><td>{{ $payment->invoice->invoice_number }}</td></tr>
<tr><td>{{ __('Invoice Total') }}</td><td>{{ formatCurrency($payment->invoice->total) }}</td></tr>
<tr><td>{{ __('Payment Method') }}</td><td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
@if ($payment->reference)<tr><td>{{ __('Reference') }}</td><td>{{ $payment->reference }}</td></tr>@endif
<tr class="grand-row"><td>{{ __('AMOUNT RECEIVED') }}</td><td>{{ formatCurrency($payment->amount) }}</td></tr>
</table>
</div>

@php
$paid = (float) $payment->invoice->paid_amount;
$balance = max(0, (float) $payment->invoice->total - $paid);
@endphp
@if ($balance > 0)
<div class="table-wrap" style="padding-top:8px;">
<table class="detail-table">
<tr><td>{{ __('Total Paid So Far') }}</td><td>{{ formatCurrency($paid) }}</td></tr>
<tr style="color:#dc2626;"><td>{{ __('Balance Remaining') }}</td><td>{{ formatCurrency($balance) }}</td></tr>
</table>
</div>@endif

@if ($payment->notes)
<div class="notes-section" style="padding:0 40px;"><div class="section-title">{{ __('NOTES') }}</div><div class="notes-text">{{ $payment->notes }}</div></div>@endif

<div class="thank-you">{{ __('Thank You!') }}</div>
<div class="footer">{{ $payment->invoice->business->name }} &bull; @if ($payment->invoice->business->email){{ $payment->invoice->business->email }} &bull; @endif {{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
</body></html>
