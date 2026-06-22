@php
$accent = $payment->invoice->business->accent_color ?? '#f97316';
$hex = ltrim($accent, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
@endphp
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{{ __('RECEIPT') }} - {{ $payment->receipt_number }}</title>
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
.customer-details { font-size:11px; color:#555; line-height:16px; }
.table-wrap { padding:20px 40px 0; }
.detail-table { width:100%; border-collapse:collapse; }
.detail-table td { padding:10px 0; border-bottom:1px solid #eee; font-size:11px; }
.detail-table td:last-child { text-align:right; font-weight:600; }
.grand-row { border-top:2px solid {{ $accent }}; padding-top:10px; margin-top:5px; font-weight:700; font-size:13px; }
.grand-row td:last-child { color:{{ $accent }}; }
.notes-section { margin-top:14px; }
.notes-text { font-size:10px; color:#333; line-height:15px; margin-top:4px; }
.balance-note { font-size:10px; color:#dc2626; margin-top:4px; }
.footer { margin:24px 40px 14px; padding-top:12px; border-top:1px solid #ddd; font-size:9px; color:#999; text-align:center; }
</style>
</head>
<body>
<div class="top-bar"><div class="doc-label">{{ __('RECEIPT') }}</div><div class="doc-number">{{ $payment->receipt_number }}</div></div>
<div class="header-flex">
<div class="left">
@if ($payment->invoice->business->logo)<img src="{{ storage_path('app/public/' . $payment->invoice->business->logo) }}" alt="Logo" class="logo-img">@endif
<div class="biz-name">{{ $payment->invoice->business->name }}</div>
<div class="biz-details">@if ($payment->invoice->business->address){!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $payment->invoice->business->address))) !!}<br>@endif
@if ($payment->invoice->business->email){{ $payment->invoice->business->email }}@endif</div>
</div>
<div class="right">
<div class="section-label">{{ __('DETAILS') }}</div>
<div style="font-size:12px;"><strong>{{ __('Date') }}:</strong> {{ $payment->payment_date->format('d M Y') }}</div>
<div style="font-size:12px;"><strong>{{ __('Invoice') }}:</strong> {{ $payment->invoice->invoice_number }}</div>
</div>
</div>

<div class="header-flex" style="padding-top:14px;">
<div class="left">
<div class="section-label">{{ __('RECEIVED FROM') }}</div>
<div class="customer-name">{{ $payment->invoice->customer?->name ?? __('Walk-in Customer') }}</div>
<div class="customer-details">@if ($payment->invoice->customer?->email){{ $payment->invoice->customer->email }}<br>@endif
@if ($payment->invoice->customer?->phone){{ $payment->invoice->customer->phone }}@endif</div>
</div>
</div>

<div class="table-wrap">
<table class="detail-table">
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
@if ($balance > 0)<div class="balance-note" style="padding:0 40px;">{{ __('Remaining Balance') }}: {{ formatCurrency($balance) }}</div>@endif

@if ($payment->notes)
<div class="notes-section" style="padding:0 40px;"><div class="section-label">{{ __('NOTES') }}</div><div class="notes-text">{{ $payment->notes }}</div></div>@endif

<div style="margin:30px 40px 0;text-align:center;font-size:28px;font-weight:300;color:{{ $accent }};">{{ __('Thank You!') }}</div>
<div class="footer">{{ $payment->invoice->business->name }} &bull; {{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
</body></html>
