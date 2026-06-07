<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('Invoice') }} - {{ $invoice->invoice_number }}</title>

@php
    $accent = $invoice->business->accent_color ?? '#f97316';
    $hex = ltrim($accent, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $dark = sprintf('#%02x%02x%02x', (int)($r * 0.55), (int)($g * 0.55), (int)($b * 0.55));
    $darker = sprintf('#%02x%02x%02x', (int)($r * 0.25), (int)($g * 0.25), (int)($b * 0.25));

    $status = 'unpaid';
    $paid = (float) $invoice->paid_amount;
    if ($paid >= (float) $invoice->total) { $status = 'paid'; }
    elseif ($paid > 0) { $status = 'partial'; }
    if ($invoice->due_date && now()->gt($invoice->due_date) && $status !== 'paid') { $status = 'overdue'; }
@endphp

<style>
@page { size: A4; margin: 0; }
* { margin:0; padding:0; box-sizing:border-box; }
html, body { width:210mm; min-height:297mm; font-family: Inter, 'DejaVu Sans', Arial, Helvetica, sans-serif; color:#333; background:#fff; }

.invoice { width:210mm; min-height:297mm; position:relative; overflow:hidden; }

/* HEADER */
.header { display:flex; height:155px; }
.company-panel { flex:1; padding:32px 40px; }

.logo-img { max-height:44px; margin-bottom:6px; display:block; }
.business-name { font-size:28px; font-weight:700; letter-spacing:.4px; }
.business-sub { margin-top:2px; font-size:11px; letter-spacing:2.5px; color:#666; text-transform:uppercase; }

.invoice-panel { width:310px; background:{{ $dark }}; color:#fff; padding:30px; }
.doc-title { font-size:36px; font-weight:700; letter-spacing:1px; margin-bottom:22px; }
.doc-meta { font-size:11.5px; line-height:23px; color:rgba(255,255,255,.8); }
.doc-meta strong { color:#fff; }

/* STATUS BADGE */
.status-badge { display:inline-block; margin-top:4px; padding:2px 10px; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:1px; border-radius:8px; }
.status-paid { background:#dcfce7; color:#166534; }
.status-unpaid { background:#fee2e2; color:#991b1b; }
.status-partial { background:#fef3c7; color:#92400e; }
.status-overdue { background:#fce7f3; color:#9d174d; }

/* BILLING */
.billing { display:flex; justify-content:space-between; padding:22px 40px; }
.bill-box { width:48%; }
.section-title { font-size:10px; font-weight:700; color:#666; letter-spacing:1px; margin-bottom:7px; text-transform:uppercase; }
.bill-box p { font-size:12.5px; line-height:20px; }

/* TABLE */
.table-container { padding:0 40px; }
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
.item-desc { font-size:9.5px; color:#777; }

/* BOTTOM */
.bottom-section { display:flex; justify-content:space-between; padding:25px 40px 0; }
.left-column { width:52%; }
.right-column { width:34%; }

/* TOTALS */
.total-table { width:100%; border-collapse:collapse; }
.total-table td { padding:7px 0; font-size:11.5px; }
.total-table td:last-child { text-align:right; font-weight:600; }
.total-table .discount td { color:#dc2626; }
.grand-total td { border-top:2px solid {{ $dark }}; padding-top:12px; font-size:17px; font-weight:700; }
.grand-total td:last-child { color:{{ $accent }}; }

/* PAYMENT INFO + RECEIPTS */
.payment-title { font-size:10px; font-weight:700; letter-spacing:1px; margin-bottom:7px; text-transform:uppercase; color:#666; }
.payment-info { font-size:10px; line-height:17px; color:#666; margin-bottom:18px; }

.receipts-title { font-size:10px; font-weight:700; letter-spacing:1px; margin-bottom:7px; text-transform:uppercase; color:#666; }
.receipts-table { width:100%; font-size:10px; border-collapse:collapse; }
.receipts-table td { padding:2px 0; }
.receipts-table td:last-child { text-align:right; }
.receipts-table .divider td { border-top:1px solid #e0e0e0; padding-top:5px; }
.receipts-table .balance-row td { font-weight:600; padding-top:2px; }
.receipts-table .balance-due td { color:#dc2626; }
.receipts-none { font-size:10px; color:#bfbfbf; }

/* NOTES */
.notes-section { margin-top:18px; }
.notes-section .section-title { margin-bottom:4px; }
.notes-text { font-size:10px; color:#666; line-height:17px; }
.notes-text p { margin-bottom:2px; }

/* TERMS */
.terms-title { font-size:10px; font-weight:700; letter-spacing:1px; margin-bottom:7px; text-transform:uppercase; color:#666; }
.terms-text { font-size:10px; color:#666; line-height:17px; }

/* QR */
.qr-section { margin-top:18px; text-align:right; }
.qr-box { width:100px; height:100px; border:1px solid #dcdcdc; display:inline-flex; align-items:center; justify-content:center; background:#fff; overflow:hidden; }
.qr-box img { width:100%; height:100%; object-fit:contain; }
.qr-title { margin-top:6px; font-size:9px; font-weight:700; letter-spacing:1px; }
.qr-note { margin-top:2px; font-size:9px; color:#777; }

/* SIGNATURE */
.signature-row { display:flex; justify-content:space-between; align-items:flex-end; padding:24px 40px 0; }
.thank-you { font-size:28px; font-weight:300; color:{{ $accent }}; }
.signature { text-align:center; }
.signature-name { font-size:24px; font-family:'DejaVu Sans', cursive; margin-bottom:3px; color:#333; }
.signature-role { font-size:10px; color:#666; }

/* FOOTER */
.footer { position:absolute; left:40px; right:40px; bottom:25px; border-top:1px solid #e8e8e8; padding-top:14px; display:flex; justify-content:space-between; font-size:9.5px; color:#666; }
</style>
</head>
<body>

<div class="invoice">

    <!-- HEADER -->
    <div class="header">
        <div class="company-panel">
            @if ($invoice->business->logo)
                <img src="{{ storage_path('app/public/' . $invoice->business->logo) }}" alt="Logo" class="logo-img">
            @endif
            <div class="business-name">{{ $invoice->business->name }}</div>
            @if ($invoice->business->address)
                <div class="business-sub">{{ __('OFFICIAL DOCUMENT') }}</div>
            @endif
        </div>
        <div class="invoice-panel">
            <div class="doc-title">{{ __('INVOICE') }}</div>
            <div class="doc-meta">
                <strong>{{ __('Invoice') }} #</strong> {{ $invoice->invoice_number }}<br>
                <strong>{{ __('Issue Date') }}</strong> {{ $invoice->issue_date->format('d M Y') }}<br>
                @if ($invoice->due_date)
                    <strong>{{ __('Due Date') }}</strong> {{ $invoice->due_date->format('d M Y') }}
                @endif
            </div>
            <span class="status-badge status-{{ $status }}">{{ __(ucfirst($status)) }}</span>
        </div>
    </div>

    <!-- BILLING -->
    <div class="billing">
        <div class="bill-box">
            <div class="section-title">{{ __('INVOICE TO') }}</div>
            <p>
                {{ $invoice->customer?->name ?? __('Walk-in Customer') }}<br>
                @if ($invoice->customer?->email){{ $invoice->customer->email }}<br>@endif
                @if ($invoice->customer?->phone){{ $invoice->customer->phone }}<br>@endif
                @if ($invoice->customer?->address){{ $invoice->customer->address }}<br>@endif
            </p>
        </div>
        <div class="bill-box">
            <div class="section-title">{{ __('FROM') }}</div>
            <p>
                {{ $invoice->business->name }}<br>
                @if ($invoice->business->email){{ $invoice->business->email }}<br>@endif
                @if ($invoice->business->address)
                    {!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $invoice->business->address))) !!}<br>
                @endif
            </p>
        </div>
    </div>

    <!-- ITEMS -->
    <div class="table-container">
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
            @forelse ($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-title">{{ $item->description ?: '—' }}</div>
                    </td>
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
    <div class="bottom-section">

        <div class="left-column">
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
                        <td colspan="2">{{ __('Total') }}</td>
                        <td>UGX {{ number_format($invoice->total, 2) }}</td>
                    </tr>
                    <tr class="balance-row">
                        <td colspan="2">{{ __('Paid') }}</td>
                        <td>UGX {{ number_format($paid, 2) }}</td>
                    </tr>
                    <tr class="balance-row balance-due">
                        <td colspan="2">{{ __('Balance Due') }}</td>
                        <td>UGX {{ number_format($balance, 2) }}</td>
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
        </div>

        <div class="right-column">
            <table class="total-table">
                <tr>
                    <td>{{ __('Sub Total') }}</td>
                    <td>UGX {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if ((float) $invoice->discount_amount > 0)
                    <tr class="discount">
                        <td>{{ __('Discount') }}</td>
                        <td>-UGX {{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @if ((float) $invoice->tax_amount > 0)
                    <tr>
                        <td>{{ $invoice->tax_name ?? __('Tax') }} ({{ $invoice->tax_rate ?? 0 }}%)</td>
                        <td>UGX {{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td>{{ __('GRAND TOTAL') }}</td>
                    <td>UGX {{ number_format($invoice->total, 2) }}</td>
                </tr>
            </table>

            <!-- QR -->
            @php
                $qrData = $invoice->business->name . "\n" . $invoice->invoice_number . "\nUGX " . number_format($invoice->total, 2);
            @endphp
            @if (class_exists(\App\Helpers\QrCode::class))
                <div class="qr-section">
                    <div class="qr-box">
                        <img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 200) }}" alt="QR">
                    </div>
                    <div class="qr-title">{{ __('SCAN TO PAY') }}</div>
                    <div class="qr-note">{{ __('Secure payment portal') }}</div>
                </div>
            @endif
        </div>
    </div>

    <!-- SIGNATURE -->
    <div class="signature-row">
        <div class="thank-you">{{ __('Thank You!') }}</div>
        <div class="signature">
            <div class="signature-name">{{ $invoice->business->name }}</div>
            <div class="signature-role">{{ __('Authorized Signature') }}</div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div>{{ $invoice->business->name }}</div>
        <div>@if ($invoice->business->email){{ $invoice->business->email }}@endif</div>
        <div>{{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
    </div>

</div>

</body>
</html>
