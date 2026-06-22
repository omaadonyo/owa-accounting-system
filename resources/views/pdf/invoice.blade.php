<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ __('Invoice') }} - {{ $invoice->invoice_number }}</title>

@php
    $accent = $invoice->business->accent_color ?? '#f97316';
    $hex = ltrim($accent, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $dark = sprintf('#%02x%02x%02x', (int)($r * 0.55), (int)($g * 0.55), (int)($b * 0.55));

    $status = 'unpaid';
    $paid = (float) $invoice->paid_amount;
    if ($paid >= (float) $invoice->total) { $status = 'paid'; }
    elseif ($paid > 0) { $status = 'partial'; }
    if ($invoice->due_date && now()->gt($invoice->due_date) && $status !== 'paid') { $status = 'overdue'; }
@endphp

<style>
@page { size: A4; margin: 0; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans', Arial, Helvetica, sans-serif; color:#333; background:#fff; font-size:12px; line-height:1.2; }

/* HEADER TABLE */
.header-table { width:100%; border-collapse:collapse; }
.header-table td { vertical-align:top; }
.company-cell { padding:32px 40px; }
.sidebar-cell { width:260px; background:{{ $dark }}; color:#fff; padding:30px; vertical-align:top; }

.logo-img { max-height:44px; margin-bottom:6px; }
.business-name { font-size:28px; font-weight:700; letter-spacing:.4px; }
.business-sub { margin-top:2px; font-size:11px; letter-spacing:2.5px; color:#666; text-transform:uppercase; }

.doc-title { font-size:36px; font-weight:700; letter-spacing:1px; margin-bottom:22px; }
.doc-meta { font-size:11.5px; line-height:23px; color:rgba(255,255,255,.8); }
.doc-meta strong { color:#fff; }

.status-badge { display:inline-block; margin-top:6px; padding:2px 10px; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:1px; }
.status-paid { background:#dcfce7; color:#166534;font-family:'DejaVu Sans'; }
.status-unpaid { background:#fee2e2; color:#991b1b;font-family:'DejaVu Sans'; }
.status-partial { background:#fef3c7; color:#92400e;font-family:'DejaVu Sans'; }
.status-overdue { background:#fce7f3; color:#9d174d;font-family:'DejaVu Sans'; }

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
.bottom-table .left-col { width:45%; }
.bottom-table .right-col { width:45%; }

/* TOTALS */
.total-table { width:100%; border-collapse:collapse; }
.total-table td { padding:6px 0; font-size:11.5px; }
.total-table td:last-child { text-align:right; font-weight:600;font-family:'DejaVu Sans'; }
.total-table .discount td { color:#dc2626;font-family:'DejaVu Sans'; }
.grand-total td { border-top:2px solid {{ $dark }}; padding-top:10px; font-size:11px; font-weight:700; }
.grand-total td:last-child { color:{{ $accent }};font-family:'DejaVu Sans'; }

/* RECEIPTS */
.receipts-title { font-size:10px; font-weight:700; color:#666;font-family:'DejaVu Sans'; letter-spacing:1px; text-transform:uppercase; margin-bottom:6px; }
.receipts-table { width:100%; font-size:10px; border-collapse:collapse; }
.receipts-table td { padding:2px 0; }
.receipts-table td:last-child { text-align:right; }
.receipts-table .divider td { border-top:1px solid #e0e0e0; padding-top:5px; }
.receipts-table .balance-row td { font-weight:600; padding-top:2px; }
.receipts-table .balance-due td { color:#dc2626; }
.receipts-none { font-size:10px; color:#bfbfbf; margin-top:4px; }

/* NOTES */
.notes-section { margin-top:16px; }
.notes-text { font-size:10px; color:#000000; line-height:10px; margin-top:4px; }
.notes-text p { margin-bottom:2px; }

/* QR */
.qr-wrap { text-align:right; margin-top:16px; }
.qr-box { width:100px; height:100px; border:1px solid #000000; display:inline-block; text-align:center; vertical-align:middle; line-height:100px; background:#fff; }
.qr-box img { vertical-align:middle; max-width:99px; max-height:99px; }
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
</style>
</head>
<body>

<!-- HEADER -->
<table class="header-table">
<tr>
    <td class="company-cell">
        @if ($invoice->business->logo)
            <img src="{{ storage_path('app/public/' . $invoice->business->logo) }}" alt="Logo" class="logo-img">
        @endif
        <br>
        {{ $invoice->business->name }}<br>
            @if ($invoice->business->email){{ $invoice->business->email }}<br>@endif
            @if ($invoice->business->address)
                {!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->address))) !!}<br>
        @endif
        <br><br>
        <div class="section-title">{{ __('INVOICE TO') }}</div>
        <div class="bill-text" style="line-height:12px;">
            {{ $invoice->customer?->name ?? __('Walk-in Customer') }}<br>
            @if ($invoice->customer?->email){{ $invoice->customer->email }}<br>@endif
            @if ($invoice->customer?->phone){{ $invoice->customer->phone }}<br>@endif
            @if ($invoice->customer?->address){{ $invoice->customer->address }}<br>@endif
        </div>
    </td>
    <td class="sidebar-cell">
        <div class="doc-title">{{ __('INVOICE') }}</div>
        <div class="doc-meta">
            <strong>{{ __('Invoice') }} #</strong> {{ $invoice->invoice_number }}<br>
            <strong>{{ __('Issue Date') }}</strong> {{ $invoice->issue_date->format('d M Y') }}<br>
            @if ($invoice->due_date)
                <strong>{{ __('Due Date') }}</strong> {{ $invoice->due_date->format('d M Y') }}
            @endif
        </div>
        <span class="status-badge status-{{ $status }}">{{ __(ucfirst($status)) }}</span>
    </td>
</tr>
</table>

<!-- BILLING -->


<!-- ITEMS -->
<div class="table-wrap">
<table class="invoice-table">
<thead>
<tr>
    <th style="width:20px;">{{ __('#') }}</th>
    <th style="text-align:left;" >{{ __('Description') }}</th>
    <th>{{ __('Qty') }}</th>
    <th>{{ __('Price') }}</th>
    <th style="text-align:right;">{{ __('Total') }}</th>
</tr>
</thead>
<tbody>
    @php

    $sn = 1;

    @endphp
@forelse ($invoice->items as $item)
    <tr>
        <td  style="width:20px;"><div class="item-title">{{ $sn++ ?: '—' }}</div></td>
        <td><div class="item-title" style="text-align:left;">{{ $item->description ?: '—' }}</div></td>
        <td>{{ number_format($item->quantity, 2) }}</td>
        <td>UGX {{ number_format($item->unit_price, 2) }}</td>
        <td style="text-align:right;">UGX {{ number_format($item->total, 2) }}</td>
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
<table class="bottom-table" >
<tr>
    <td class="left-col">
        @php
            $payments = $invoice->payments()->orderBy('created_at', 'desc')->get();
            $balance = max(0, (float) $invoice->total - $paid);
        @endphp

        <div class="receipts-title">{{ __('PAYMENT HISTORY') }}</div>
        @if ($payments->isNotEmpty())
            <table class="receipts-table">
                @foreach ($payments as $p)
                    <tr>
                        <td style="font-family:monospace;">{{ $p->receipt_number }}</td>
                        <td style="color:#999;">{{ $p->created_at->format('d M Y') }}</td>
                        <td>UGX {{ number_format($p->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="divider"><td colspan="3"></td></tr>
                <tr class="balance-row">
                    <td  style="font-family:monospace;" colspan="2">{{ __('Total') }}</td>
                    <td  style="font-family:monospace;" >UGX {{ number_format($invoice->total, 2) }}</td>
                </tr>
                <tr class="balance-row">
                    <td  style="font-family:monospace;" colspan="2">{{ __('Paid') }}</td>
                    <td  style="font-family:monospace;" >UGX {{ number_format($paid, 2) }}</td>
                </tr>
                <tr class="balance-row balance-due">
                    <td  style="font-family:monospace;" colspan="2">{{ __('Balance Due') }}</td>
                    <td  style="font-family:monospace;" >UGX {{ number_format($balance, 2) }}</td>
                </tr>
            </table>
        @else
            <p class="receipts-none">{{ __('No payments recorded yet.') }}</p>
        @endif

        @if ($invoice->notes || $invoice->business->invoice_notes)
            <div class="notes-section">
                <div class="section-title">{{ __('NOTES') }}</div>
                <div class="notes-text">
                    @if ($invoice->notes)
                        {!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->notes))) !!}
                    @endif
                    @if ($invoice->business->invoice_notes)
                        <p style="font-style:italic;margin-top:4px;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->invoice_notes))) !!}</p>
                    @endif
                </div>
            </div>
        @endif
    </td>
    <td class="right-col">
        <table class="total-table">
            <tr>
                <td>{{ __('Sub Total') }}</td>
                <td>UGX {{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if ((float) $invoice->discount_amount > 0)
                <tr class="discount">
                    <td>{{ __('Discount') }}</td>
                    <td style="font-family:monospace;">-UGX {{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if ((float) $invoice->tax_amount > 0)
                <tr>
                    <td>{{ $invoice->tax_name ?? __('Tax') }} ({{ $invoice->tax_rate ?? 0 }}%)</td>
                    <td  style="font-family:monospace;">UGX {{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td  style="font-family:monospace;">{{ __('GRAND TOTAL') }}</td>
                <td  style="font-family:monospace;">UGX {{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>

        @php
            $qrData = $invoice->business->name . "\n" . $invoice->invoice_number . "\nUGX " . number_format($invoice->total, 2);
        @endphp
        @if (class_exists(\App\Helpers\QrCode::class))
            <div class="qr-wrap">
                <div class="qr-box">
                    <img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 200) }}" alt="QR">
                </div>
                <div class="qr-title">{{ __('SCAN TO PAY') }}</div>
                <div class="qr-note">{{ __('Secure payment portal') }}</div>
            </div>
        @endif
    </td>
</tr>
</table>

<!-- SIGNATURE -->


<!-- FOOTER -->
<div class="footer">
<table class="footer-table">
<tr>
    <td>{{ $invoice->business->name }}</td>
    <td style="text-align:center;">@if ($invoice->business->email){{ $invoice->business->email }}@endif</td>
    <td style="text-align:right;">{{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</td>
</tr>
</table>
</div>

</body>
</html>
