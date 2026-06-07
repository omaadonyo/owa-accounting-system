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
        if (! auth()->user()->business) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
        }
    }

    public function with(): array
    {
        $businessId = auth()->user()->business->id;

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
            'quotationCount' => Quotation::where('business_id', $businessId)->when($dateRange, fn($q) => $applyDateFilter($q))->count(),
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
        $business = auth()->user()->business;
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

<div style="width: 80%; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Reports') }}</flux:heading>
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="period" class="w-44">
                <option value="all">{{ __('All Time') }}</option>
                <option value="30d">{{ __('Last 30 Days') }}</option>
                <option value="90d">{{ __('Last 90 Days') }}</option>
                <option value="12m">{{ __('Last 12 Months') }}</option>
            </flux:select>
            <flux:button wire:click="exportPdf" variant="ghost" icon="arrow-down-tray">
                {{ __('PDF') }}
            </flux:button>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="relative overflow-hidden p-5">
            <div class="absolute right-3 top-3 flex size-10 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                <flux:icon name="banknotes" variant="solid" class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Total Revenue') }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">UGX {{ number_format($totalRevenue, 0) }}</div>
            <div class="mt-1 flex items-center gap-1 text-xs text-neutral-400">
                <span>{{ $paymentCount }} {{ __('payments') }}</span>
            </div>
        </flux:card>

        <flux:card class="relative overflow-hidden p-5">
            <div class="absolute right-3 top-3 flex size-10 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20">
                <flux:icon name="document-text" variant="solid" class="size-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Total Invoiced') }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">UGX {{ number_format($totalInvoiced, 0) }}</div>
            <div class="mt-1 flex items-center gap-1 text-xs text-neutral-400">
                <span>{{ $invoiceCount }} {{ __('invoices') }}</span>
                <span>&middot;</span>
                <span class="text-emerald-500">{{ $paidInvoices }} {{ __('paid') }}</span>
            </div>
        </flux:card>

        <flux:card class="relative overflow-hidden p-5">
            <div class="absolute right-3 top-3 flex size-10 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/20">
                <flux:icon name="exclamation-triangle" variant="solid" class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Outstanding') }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">UGX {{ number_format(max($totalInvoiced - $totalRevenue, 0), 0) }}</div>
            <div class="mt-1 flex items-center gap-1 text-xs text-neutral-400">
                <span class="text-amber-500">{{ $pendingInvoices }} {{ __('unpaid invoices') }}</span>
            </div>
        </flux:card>

        <flux:card class="relative overflow-hidden p-5">
            <div class="absolute right-3 top-3 flex size-10 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-900/20">
                <flux:icon name="users" variant="solid" class="size-5 text-violet-600 dark:text-violet-400" />
            </div>
            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Customers') }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $customerCount }}</div>
            <div class="mt-1 flex items-center gap-1 text-xs text-neutral-400">
                <span>{{ $quotationCount }} {{ __('quotes') }}</span>
                <span>&middot;</span>
                <span>{{ $invoiceCount }} {{ __('invoices') }}</span>
            </div>
        </flux:card>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:card class="p-5">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="sm">{{ __('Payment Methods') }}</flux:heading>
                <flux:icon name="credit-card" class="size-4 text-neutral-400" />
            </div>
            <div class="space-y-3">
                @forelse ($paymentMethodBreakdown as $method)
                    @php
                        $methodColors = [
                            'cash' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'text' => 'text-emerald-600 dark:text-emerald-400', 'dot' => 'bg-emerald-500'],
                            'bank_transfer' => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'text' => 'text-blue-600 dark:text-blue-400', 'dot' => 'bg-blue-500'],
                            'mobile_money' => ['bg' => 'bg-violet-50 dark:bg-violet-900/20', 'text' => 'text-violet-600 dark:text-violet-400', 'dot' => 'bg-violet-500'],
                            'credit_card' => ['bg' => 'bg-amber-50 dark:bg-amber-900/20', 'text' => 'text-amber-600 dark:text-amber-400', 'dot' => 'bg-amber-500'],
                            'cheque' => ['bg' => 'bg-sky-50 dark:bg-sky-900/20', 'text' => 'text-sky-600 dark:text-sky-400', 'dot' => 'bg-sky-500'],
                        ];
                        $colors = $methodColors[$method->payment_method] ?? ['bg' => 'bg-neutral-50 dark:bg-neutral-800', 'text' => 'text-neutral-600 dark:text-neutral-400', 'dot' => 'bg-neutral-400'];
                    @endphp
                    <div class="flex items-center justify-between rounded-lg p-3 {{ $colors['bg'] }}">
                        <div class="flex items-center gap-3">
                            <span class="flex size-2 rounded-full {{ $colors['dot'] }}"></span>
                            <span class="text-sm font-medium capitalize {{ $colors['text'] }}">{{ str_replace('_', ' ', $method->payment_method) }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-neutral-900 dark:text-white">UGX {{ number_format($method->total, 0) }}</div>
                            <div class="text-xs text-neutral-400">{{ $method->count }} {{ __('payments') }}</div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-neutral-400">{{ __('No payments recorded.') }}</p>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="p-5">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="sm">{{ __('Highest Invoices') }}</flux:heading>
                <flux:icon name="arrow-trending-up" class="size-4 text-neutral-400" />
            </div>
            <div class="space-y-3">
                @forelse ($topInvoices as $invoice)
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-neutral-900 dark:text-white">{{ $invoice->invoice_number }}</span>
                                <flux:badge variant="pill" size="sm" :color="match($invoice->status) { 'paid' => 'lime', 'overdue' => 'red', 'sent' => 'blue', default => 'neutral' }" :icon="match($invoice->status) { 'paid' => 'check-circle', 'overdue' => 'exclamation-triangle', 'sent' => 'paper-airplane', default => 'clock' }">
                                    {{ ucfirst($invoice->status) }}
                                </flux:badge>
                            </div>
                            <div class="text-xs text-neutral-400 truncate">{{ $invoice->customer?->name ?? __('Walk-in') }}</div>
                        </div>
                        <span class="ml-3 shrink-0 text-sm font-semibold text-neutral-900 dark:text-white">UGX {{ number_format($invoice->total, 0) }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-neutral-400">{{ __('No invoices yet.') }}</p>
                @endforelse
            </div>
        </flux:card>
    </div>

    <flux:card class="p-5">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="sm">{{ __('Recent Payments') }}</flux:heading>
            <flux:icon name="clock" class="size-4 text-neutral-400" />
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Receipt') }}</flux:table.column>
                <flux:table.column>{{ __('Invoice') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Amount') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Method') }}</flux:table.column>
                <flux:table.column>{{ __('Recorded By') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
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
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs font-medium text-indigo-500">{{ $payment->receipt_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $payment->invoice?->invoice_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-medium">UGX {{ number_format($payment->amount, 0) }}</flux:table.cell>
                        <flux:table.cell class="text-neutral-500">{{ $payment->payment_date->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="pill" size="sm" :color="$badgeColor" :icon="$badgeIcon">
                                {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-neutral-500">{{ $payment->creator?->name ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <div class="flex flex-col items-center py-8 text-center">
                                <flux:heading class="text-neutral-400">{{ __('No payments yet') }}</flux:heading>
                                <flux:icon name="credit-card" class="mt-2 size-6 text-neutral-300" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
