<?php

use App\Models\Customer;
use App\Models\Fabric;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProductService;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reports')] class extends Component {
    public string $period = 'all';

    public function mount(): void
    {
        if (! currentBusiness()) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
        }
    }

    public function with(): array
    {
        $businessId = currentBusiness()->id;

        $dateRange = match ($this->period) {
            '30d' => [now()->subDays(30), now()],
            '90d' => [now()->subDays(90), now()],
            '12m' => [now()->subYear(), now()],
            default => null,
        };

        $applyDateFilter = function ($query) use ($dateRange) {
            if ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }
            return $query;
        };

        $invoices = Invoice::where('business_id', $businessId)->when($dateRange, fn($q) => $q->whereBetween('issue_date', $dateRange));
        $payments = Payment::whereHas('invoice', fn($q) => $q->where('business_id', $businessId))
            ->when($dateRange, fn($q) => $q->whereBetween('payment_date', $dateRange));
        $quotations = Quotation::where('business_id', $businessId)->when($dateRange, fn($q) => $applyDateFilter($q));

        return [
            'totalRevenue' => (clone $payments)->sum('amount'),
            'paymentCount' => (clone $payments)->count(),
            'totalInvoiced' => (clone $invoices)->sum('total'),
            'invoiceCount' => (clone $invoices)->count(),
            'paidInvoices' => (clone $invoices)->where('status', 'paid')->count(),
            'pendingInvoices' => (clone $invoices)->whereIn('status', ['draft', 'sent', 'overdue'])->count(),
            'customerCount' => Customer::where('business_id', $businessId)->when($dateRange, fn($q) => $applyDateFilter($q))->count(),
            'productCount' => ProductService::where('business_id', $businessId)->when($dateRange, fn($q) => $applyDateFilter($q))->count(),
            'fabricCount' => Fabric::where('business_id', $businessId)->when($dateRange, fn($q) => $applyDateFilter($q))->count(),
            'quotationCount' => (clone $quotations)->count(),
            'convertedQuotations' => (clone $quotations)->where('status', 'converted')->count(),
            'recentPayments' => (clone $payments)->with('invoice', 'creator')->latest()->take(10)->get(),
            'topInvoices' => (clone $invoices)->with('customer')->orderByDesc('total')->take(5)->get(),
            'paymentMethodBreakdown' => (clone $payments)
                ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('payment_method')
                ->get(),
            'period' => $this->period,
        ];
    }

    public function exportPdf()
    {
        $business = currentBusiness();
        $data = $this->with();
        $data['business'] = $business;
        $data['periodLabel'] = match ($this->period) {
            '30d' => __('Last 30 Days'),
            '90d' => __('Last 90 Days'),
            '12m' => __('Last 12 Months'),
            default => __('All Time'),
        };

        $pdf = Pdf::loadView('pdf.reports', $data);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            __('reports') . '-' . now()->format('Y-m-d') . '.pdf'
        );
    }
}; ?>

<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Reports') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Business performance at a glance') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-3">
            <div class="custom-select relative w-44">
                <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                    <span wire:ignore data-cs-display>{{ __('All Time') }}</span>
                    <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                        <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-accent focus:ring-1 focus:ring-accent/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                    </div>
                    <div data-cs-options class="max-h-48 overflow-y-auto py-1">
                        <button type="button" data-cs-option data-cs-value="all" data-cs-label="All Time" class="cs-selected flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('All Time') }}</button>
                        <button type="button" data-cs-option data-cs-value="30d" data-cs-label="Last 30 Days" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Last 30 Days') }}</button>
                        <button type="button" data-cs-option data-cs-value="90d" data-cs-label="Last 90 Days" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Last 90 Days') }}</button>
                        <button type="button" data-cs-option data-cs-value="12m" data-cs-label="Last 12 Months" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Last 12 Months') }}</button>
                    </div>
                </div>
                <select wire:model.live="period" class="sr-only">
                    <option value="all">{{ __('All Time') }}</option>
                    <option value="30d">{{ __('Last 30 Days') }}</option>
                    <option value="90d">{{ __('Last 90 Days') }}</option>
                    <option value="12m">{{ __('Last 12 Months') }}</option>
                </select>
            </div>
            <flux:button wire:click="exportPdf" variant="primary" icon="arrow-down-tray">
                {{ __('Export PDF') }}
            </flux:button>
        </div>
    </div>

    {{-- KPI Cards Row 1 --}}
    <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm dark:border-emerald-800/30 dark:from-emerald-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-emerald-100/60 dark:bg-emerald-900/30">
                <flux:icon name="banknotes" variant="solid" class="size-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Total Revenue') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($totalRevenue, 0) }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $paymentCount }} {{ __('payments') }}</span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm dark:border-blue-800/30 dark:from-blue-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-blue-100/60 dark:bg-blue-900/30">
                <flux:icon name="document-text" variant="solid" class="size-6 text-blue-600 dark:text-blue-400" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">{{ __('Total Invoiced') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($totalInvoiced, 0) }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                <span class="rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ $invoiceCount }} {{ __('invoices') }}</span>
                <span class="text-neutral-300 dark:text-neutral-600">|</span>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $paidInvoices }} {{ __('paid') }}</span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm dark:border-amber-800/30 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-amber-100/60 dark:bg-amber-900/30">
                <flux:icon name="exclamation-triangle" variant="solid" class="size-6 text-amber-600 dark:text-amber-400" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('Outstanding') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency(max($totalInvoiced - $totalRevenue, 0), 0) }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                <span class="rounded-full bg-red-100 px-2 py-0.5 font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">{{ $pendingInvoices }} {{ __('unpaid') }}</span>
                @php $collectionRate = $totalInvoiced > 0 ? round(($totalRevenue / $totalInvoiced) * 100, 1) : 0; @endphp
                <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ $collectionRate }}% {{ __('collected') }}</span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm dark:border-violet-800/30 dark:from-violet-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-violet-100/60 dark:bg-violet-900/30">
                <flux:icon name="users" variant="solid" class="size-6 text-violet-600 dark:text-violet-400" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">{{ __('Customers') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $customerCount }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                <span class="rounded-full bg-violet-100 px-2 py-0.5 font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">{{ $quotationCount }} {{ __('quotes') }}</span>
                <span class="text-neutral-300 dark:text-neutral-600">|</span>
                <span class="rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ $invoiceCount }} {{ __('invoices') }}</span>
            </div>
        </div>
    </div>

    {{-- KPI Cards Row 2 --}}
    <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative overflow-hidden rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm dark:border-rose-800/30 dark:from-rose-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-rose-100/60 dark:bg-rose-900/30">
                <flux:icon name="cube" variant="solid" class="size-6 text-rose-600 dark:text-rose-400" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">{{ __('Products') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $productCount }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                <span class="rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ $fabricCount }} {{ __('fabrics') }}</span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm dark:border-sky-800/30 dark:from-sky-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-sky-100/60 dark:bg-sky-900/30">
                <flux:icon name="chart-bar" variant="solid" class="size-6 text-sky-600 dark:text-sky-400" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-sky-600 dark:text-sky-400">{{ __('Quotations') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $quotationCount }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                <span class="rounded-full bg-sky-100 px-2 py-0.5 font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ $quotationCount }} {{ __('total') }}</span>
                <span class="text-neutral-300 dark:text-neutral-600">|</span>
                <span class="rounded-full bg-indigo-100 px-2 py-0.5 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $convertedQuotations }} {{ __('converted') }}</span>
            </div>
        </div>

        @php
            $conversionRate = $quotationCount > 0 ? round(($convertedQuotations / $quotationCount) * 100, 1) : 0;
            $barColor = $conversionRate >= 60 ? 'bg-emerald-500' : ($conversionRate >= 30 ? 'bg-amber-500' : 'bg-rose-500');
        @endphp
        <div class="relative overflow-hidden rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-5 shadow-sm dark:border-indigo-800/30 dark:from-indigo-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-indigo-100/60 dark:bg-indigo-900/30">
                <flux:icon name="arrow-trending-up" variant="solid" class="size-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Conversion Rate') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $conversionRate }}%</p>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $conversionRate }}%"></div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-5 shadow-sm dark:border-teal-800/30 dark:from-teal-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-teal-100/60 dark:bg-teal-900/30">
                <flux:icon name="receipt-percent" variant="solid" class="size-6 text-teal-600 dark:text-teal-400" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-600 dark:text-teal-400">{{ __('Avg per Invoice') }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $invoiceCount > 0 ? formatCurrency(round($totalInvoiced / $invoiceCount), 0) : formatCurrency(0, 0) }}</p>
            @php $avgPayment = $paymentCount > 0 ? round($totalRevenue / $paymentCount) : 0; @endphp
            <div class="mt-2 flex items-center gap-2 text-xs">
                <span class="rounded-full bg-teal-100 px-2 py-0.5 font-medium text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">{{ formatCurrency($avgPayment, 0) }}/{{ __('payment') }}</span>
            </div>
        </div>
    </div>

    {{-- Charts / Details Section --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Payment Methods --}}
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                <div class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-900/30">
                        <flux:icon name="credit-card" class="size-4 text-violet-600 dark:text-violet-400" />
                    </div>
                    <flux:heading size="sm">{{ __('Payment Methods') }}</flux:heading>
                </div>
                @php $pmTotal = $paymentMethodBreakdown->sum('total'); @endphp
                <span class="text-xs font-medium text-neutral-400">{{ $paymentMethodBreakdown->count() }} {{ __('methods') }}</span>
            </div>
            <div class="p-5">
                @if ($paymentMethodBreakdown->isNotEmpty())
                    @php
                        $methodColors = [
                            'cash' => ['bg' => 'bg-emerald-500', 'card' => 'border-emerald-200 dark:border-emerald-800/40', 'icon' => 'bg-emerald-100 dark:bg-emerald-900/30', 'icon-color' => 'text-emerald-600 dark:text-emerald-400'],
                            'bank_transfer' => ['bg' => 'bg-blue-500', 'card' => 'border-blue-200 dark:border-blue-800/40', 'icon' => 'bg-blue-100 dark:bg-blue-900/30', 'icon-color' => 'text-blue-600 dark:text-blue-400'],
                            'mobile_money' => ['bg' => 'bg-violet-500', 'card' => 'border-violet-200 dark:border-violet-800/40', 'icon' => 'bg-violet-100 dark:bg-violet-900/30', 'icon-color' => 'text-violet-600 dark:text-violet-400'],
                            'credit_card' => ['bg' => 'bg-amber-500', 'card' => 'border-amber-200 dark:border-amber-800/40', 'icon' => 'bg-amber-100 dark:bg-amber-900/30', 'icon-color' => 'text-amber-600 dark:text-amber-400'],
                            'cheque' => ['bg' => 'bg-sky-500', 'card' => 'border-sky-200 dark:border-sky-800/40', 'icon' => 'bg-sky-100 dark:bg-sky-900/30', 'icon-color' => 'text-sky-600 dark:text-sky-400'],
                        ];
                        $methodIcons = [
                            'cash' => 'banknotes',
                            'bank_transfer' => 'building-bank',
                            'mobile_money' => 'device-phone-mobile',
                            'credit_card' => 'credit-card',
                            'cheque' => 'document-text',
                        ];
                    @endphp
                    @foreach ($paymentMethodBreakdown as $method)
                        @php
                            $mc = $methodColors[$method->payment_method] ?? ['bg' => 'bg-neutral-500', 'card' => 'border-neutral-200 dark:border-neutral-700', 'icon' => 'bg-neutral-100 dark:bg-neutral-800', 'icon-color' => 'text-neutral-600 dark:text-neutral-400'];
                            $mi = $methodIcons[$method->payment_method] ?? 'clock';
                            $pct = $pmTotal > 0 ? round(($method->total / $pmTotal) * 100, 1) : 0;
                        @endphp
                        <div class="mb-3 last:mb-0">
                            <div class="mb-1.5 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="{{ $mi }}" class="size-4 {{ $mc['icon-color'] }}" />
                                    <span class="text-sm font-medium capitalize text-neutral-900 dark:text-white">{{ str_replace('_', ' ', $method->payment_method) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">{{ formatCurrency($method->total, 0) }}</span>
                                    <span class="ml-1 text-xs text-neutral-400">({{ $pct }}%)</span>
                                </div>
                            </div>
                            <div class="relative h-2.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <div class="h-full rounded-full transition-all duration-500 {{ $mc['bg'] }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="mt-0.5 text-xs text-neutral-400">{{ $method->count }} {{ __('transactions') }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="flex flex-col items-center py-8">
                        <flux:icon name="credit-card" class="size-8 text-neutral-300 dark:text-neutral-600" />
                        <p class="mt-2 text-sm text-neutral-400">{{ __('No payments recorded yet.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Highest Invoices --}}
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                <div class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon name="arrow-trending-up" class="size-4 text-amber-600 dark:text-amber-400" />
                    </div>
                    <flux:heading size="sm">{{ __('Highest Invoices') }}</flux:heading>
                </div>
                <span class="text-xs font-medium text-neutral-400">{{ __('Top 5') }}</span>
            </div>
            <div class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                @forelse ($topInvoices as $i => $invoice)
                    @php
                        $rankColors = ['text-amber-500', 'text-neutral-400', 'text-amber-700', 'text-blue-400', 'text-violet-400'];
                        $rankIcon = $i === 0 ? 'trophy' : 'number-' . strval($i + 1);
                    @endphp
                    <div class="flex items-center gap-4 px-5 py-3.5 transition hover:bg-neutral-50 dark:hover:bg-white/5">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-xs font-bold {{ $rankColors[$i] ?? 'text-neutral-500' }} dark:bg-neutral-800">
                            {{ $i + 1 }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-neutral-900 dark:text-white">{{ $invoice->invoice_number }}</span>
                                <flux:badge variant="pill" size="sm"
                                    :color="match($invoice->status) { 'paid' => 'lime', 'overdue' => 'red', 'sent' => 'blue', default => 'neutral' }"
                                    :icon="match($invoice->status) { 'paid' => 'check-circle', 'overdue' => 'exclamation-triangle', 'sent' => 'paper-airplane', default => 'clock' }"
                                    class="shrink-0">
                                    {{ ucfirst($invoice->status) }}
                                </flux:badge>
                            </div>
                            <p class="text-xs text-neutral-400 truncate">{{ $invoice->customer?->name ?? __('Walk-in') }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-neutral-900 dark:text-white">{{ formatCurrency($invoice->total, 0) }}</span>
                    </div>
                @empty
                    <div class="flex flex-col items-center py-8">
                        <flux:icon name="document-text" class="size-8 text-neutral-300 dark:text-neutral-600" />
                        <p class="mt-2 text-sm text-neutral-400">{{ __('No invoices yet.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Payments Table --}}
    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
        <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
            <div class="flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900/30">
                    <flux:icon name="clock" class="size-4 text-sky-600 dark:text-sky-400" />
                </div>
                <flux:heading size="sm">{{ __('Recent Payments') }}</flux:heading>
            </div>
            <span class="text-xs font-medium text-neutral-400">{{ __('Last 10') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-100 bg-neutral-50/80 text-left dark:border-neutral-700/50 dark:bg-neutral-800/30">
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Receipt') }}</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Invoice') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Amount') }}</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Date') }}</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Method') }}</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Recorded By') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                    @forelse ($recentPayments as $payment)
                        @php
                            $methodBadgeColors = [
                                'cash' => 'lime',
                                'bank_transfer' => 'blue',
                                'mobile_money' => 'violet',
                                'credit_card' => 'amber',
                                'cheque' => 'sky',
                            ];
                            $methodIcons = [
                                'cash' => 'banknotes',
                                'bank_transfer' => 'building-bank',
                                'mobile_money' => 'device-phone-mobile',
                                'credit_card' => 'credit-card',
                                'cheque' => 'document-text',
                            ];
                            $badgeColor = $methodBadgeColors[$payment->payment_method] ?? 'neutral';
                            $badgeIcon = $methodIcons[$payment->payment_method] ?? 'clock';
                        @endphp
                        <tr class="transition hover:bg-neutral-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $payment->receipt_number ?? '—' }}</td>
                            <td class="px-5 py-3 font-medium text-neutral-900 dark:text-white">{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-neutral-900 dark:text-white">{{ formatCurrency($payment->amount, 0) }}</td>
                            <td class="px-5 py-3 text-neutral-500">{{ $payment->payment_date->format('d M Y') }}</td>
                            <td class="px-5 py-3">
                                <flux:badge variant="pill" size="sm" :color="$badgeColor" :icon="$badgeIcon">
                                    {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-3 text-neutral-500">{{ $payment->creator?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center py-10 text-center">
                                    <flux:icon name="credit-card" class="size-8 text-neutral-300 dark:text-neutral-600" />
                                    <flux:heading class="mt-2 text-neutral-400">{{ __('No payments yet') }}</flux:heading>
                                    <p class="mt-1 text-xs text-neutral-400">{{ __('Payments will appear here once recorded.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
