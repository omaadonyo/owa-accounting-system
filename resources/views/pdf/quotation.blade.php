<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('Quotation') }} - {{ $quotation->quotation_number }}</title>

@php
    $accent = $quotation->business->accent_color ?? '#f97316';
    // Generate darker shade (~60% brightness) for sidebar
    $hex = ltrim($accent, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $dark = sprintf('#%02x%02x%02x', (int)($r * 0.55), (int)($g * 0.55), (int)($b * 0.55));
    // Very dark shade (~30% brightness)
    $darker = sprintf('#%02x%02x%02x', (int)($r * 0.25), (int)($g * 0.25), (int)($b * 0.25));
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

/* NOTES */
.notes-section { margin-top:18px; }
.notes-section .section-title { margin-bottom:4px; }
.notes-text { font-size:10px; color:#666; line-height:17px; }
.notes-text p { margin-bottom:2px; }

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
            @if ($quotation->business->logo)
                <img src="{{ storage_path('app/public/' . $quotation->business->logo) }}" alt="Logo" class="logo-img">
            @endif
            <div class="business-name">{{ $quotation->business->name }}</div>
            @if ($quotation->business->address)
                <div class="business-sub">{{ __('OFFICIAL DOCUMENT') }}</div>
            @endif
        </div>
        <div class="invoice-panel">
            <div class="doc-title">{{ __('QUOTATION') }}</div>
            <div class="doc-meta">
                <strong>{{ __('Quotation') }} #</strong> {{ $quotation->quotation_number }}<br>
                <strong>{{ __('Issue Date') }}</strong> {{ $quotation->issue_date->format('d M Y') }}<br>
                @if ($quotation->valid_until)
                    <strong>{{ __('Valid Until') }}</strong> {{ $quotation->valid_until->format('d M Y') }}
                @endif
            </div>
        </div>
    </div>

    <!-- BILLING -->
    <div class="billing">
        <div class="bill-box">
            <div class="section-title">{{ __('QUOTE TO') }}</div>
            <p>
                {{ $quotation->customer?->name ?? __('Walk-in Customer') }}<br>
                @if ($quotation->customer?->email){{ $quotation->customer->email }}<br>@endif
                @if ($quotation->customer?->phone){{ $quotation->customer->phone }}<br>@endif
                @if ($quotation->customer?->address){{ $quotation->customer->address }}<br>@endif
            </p>
        </div>
        <div class="bill-box">
            <div class="section-title">{{ __('FROM') }}</div>
            <p>
                {{ $quotation->business->name }}<br>
                @if ($quotation->business->email){{ $quotation->business->email }}<br>@endif
                @if ($quotation->business->address)
                    {!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->address))) !!}<br>
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
            @forelse ($quotation->items as $item)
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
            @if ($quotation->notes || $quotation->business->quotes_notes)
                <div class="notes-section">
                    <div class="section-title">{{ __('NOTES') }}</div>
                    <div class="notes-text">
                        @if ($quotation->notes)
                            {!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->notes))) !!}
                        @endif
                        @if ($quotation->business->quotes_notes)
                            <p style="font-style:italic;margin-top:4px;">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $quotation->business->quotes_notes))) !!}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="right-column">
            <table class="total-table">
                <tr>
                    <td>{{ __('Sub Total') }}</td>
                    <td>UGX {{ number_format($quotation->subtotal, 2) }}</td>
                </tr>
                @if ((float) $quotation->discount_amount > 0)
                    <tr class="discount">
                        <td>{{ __('Discount') }}</td>
                        <td>-UGX {{ number_format($quotation->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @if ((float) $quotation->tax_amount > 0)
                    <tr>
                        <td>{{ $quotation->tax_name ?? __('Tax') }} ({{ $quotation->tax_rate ?? 0 }}%)</td>
                        <td>UGX {{ number_format($quotation->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td>{{ __('GRAND TOTAL') }}</td>
                    <td>UGX {{ number_format($quotation->total, 2) }}</td>
                </tr>
            </table>

            <!-- QR -->
            @php
                $qrData = $quotation->business->name . "\n" . $quotation->quotation_number . "\nUGX " . number_format($quotation->total, 2);
            @endphp
            @if (class_exists(\App\Helpers\QrCode::class))
                <div class="qr-section">
                    <div class="qr-box">
                        <img src="{{ \App\Helpers\QrCode::generateDataUri($qrData, 200) }}" alt="QR">
                    </div>
                    <div class="qr-title">{{ __('SCAN TO VERIFY') }}</div>
                    <div class="qr-note">{{ __('Secure document') }}</div>
                </div>
            @endif
        </div>
    </div>

    <!-- SIGNATURE -->
    <div class="signature-row">
        <div class="thank-you">{{ __('Thank You!') }}</div>
        <div class="signature">
            <div class="signature-name">{{ $quotation->business->name }}</div>
            <div class="signature-role">{{ __('Authorized Signature') }}</div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div>{{ $quotation->business->name }}</div>
        <div>@if ($quotation->business->email){{ $quotation->business->email }}@endif</div>
        <div>{{ __('Generated :date', ['date' => now()->format('d M Y')]) }}</div>
    </div>

</div>

</body>
</html>
