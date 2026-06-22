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
body { font-family: 'DejaVu Sans', Arial, sans-serif; color:#222; font-size:11px; line-height:1.35; }
.page { padding:28px 32px; }
.top-row { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid {{ $accent }}; padding-bottom:14px; margin-bottom:14px; }
.logo-img { max-height:38px; margin-bottom:4px; }
.biz-name { font-size:20px; font-weight:700; }
.biz-info { font-size:9px; color:#666; line-height:15px; }
.doc-label { font-size:32px; font-weight:700; color:{{ $accent }}; letter-spacing:1px; }
.doc-meta { text-align:right; font-size:10px; line-height:18px; color:#555; }
.doc-meta strong { color:#222; }
.addr-label { font-size:8px; font-weight:700; color:#999; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:3px; }
.addr-name { font-size:13px; font-weight:600; }
.addr-text { font-size:10px; color:#555; line-height:15px; }
.detail-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
.detail-table td { padding:8px 0; border-bottom:1px solid #eee; font-size:10px; }
.detail-table td:last-child { text-align:right; font-weight:600; }
.grand-row td { border-top:2px solid {{ $accent }}; padding-top:8px; font-weight:700; font-size:12px; }
.grand-row td:last-child { color:{{ $accent }}; }
.balance-note { font-size:9px; color:#dc2626; margin-top:6px; }
.notes-mini { margin-top:10px; font-size:9px; color:#444; line-height:14px; }
.footer { margin-top:18px; padding-top:10px; border-top:1px solid #ddd; font-size:8px; color:#999; text-align:center; }
</style>
</head>
<body>
<div class="page">
<div class="top-row">
<div>
@if ($payment->invoice->business->logo)<img src="{{ storage_path('app/public/' . $payment->invoice->business->logo) }}" alt="Logo" class="logo-img">@endif
<div class="biz-name">{{ $payment->invoice->business->name }}</div>
<div class="biz-info">@if ($payment->invoice->business->email){{ $payment->invoice->business->email }}<br>@endif
@if ($payment->invoice->business->phone){{ $payment->invoice->business->phone }}@endif</div>
</div>
<div style="text-align:right;">
<div class="doc-label">{{ __('RECEIPT') }}</div>
<div class="doc-meta">
<strong>{{ __('Number') }}:</strong> {{ $payment->receipt_number }}<br>
<strong>{{ __('Date') }}:</strong> {{ $payment->payment_date->format('d M Y') }}<br>
<strong>{{ __('Invoice') }}:</strong> {{ $payment->invoice->invoice_number }}
</div>
</div>
</div>

<div class="addr-label">{{ __('RECEIVED FROM') }}</div>
<div class="addr-name">{{ $payment->invoice->customer?->name ?? __('Walk-in Customer') }}</div>
<div class="addr-text">@if ($payment->invoice->customer?->email){{ $payment->invoice->customer->email }}<br>@endif
@if ($payment->invoice->customer?->phone){{ $payment->invoice->customer->phone }}@endif</div>

<div style="margin-top:14px;">
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
@if ($balance > 0)<div class="balance-note">{{ __('Remaining Balance') }}: {{ formatCurrency($balance) }}</div>@endif

@if ($payment->notes)<div class="notes-mini"><strong style="font-size:8px;letter-spacing:1px;">{{ __('NOTES') }}</strong><br>{{ $payment->notes }}</div>@endif

<div style="margin-top:20px;text-align:center;font-size:24px;font-weight:300;color:{{ $accent }};">{{ __('Thank You!') }}</div>
<div class="footer">{{ $payment->invoice->business->name }} &bull; {{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
</div>
</body></html>
