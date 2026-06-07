<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto" style="width: 80%;">
        <div class="mb-6">
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Overview of your business activity.') }}</flux:subheading>
        </div>

        @php
            $business = auth()->user()->business;
            $customersCount = $business?->customers()->count() ?? 0;
            $fabricsCount = $business?->fabrics()->count() ?? 0;
            $productsCount = $business?->productsServices()->count() ?? 0;
            $quotationsCount = $business ? \App\Models\Quotation::where('business_id', $business->id)->count() : 0;
            $invoicesCount = $business ? \App\Models\Invoice::where('business_id', $business->id)->count() : 0;
            $pendingInvoices = $business ? \App\Models\Invoice::where('business_id', $business->id)->whereIn('status', ['draft', 'sent'])->count() : 0;
            $pendingCustomerRequests = $business ? \App\Models\CustomerQuotation::where('business_id', $business->id)->where('status', 'pending')->count() : 0;
            $recentQuotations = $business ? \App\Models\Quotation::where('business_id', $business->id)->with('customer')->latest()->take(5)->get() : collect();
            $recentInvoices = $business ? \App\Models\Invoice::where('business_id', $business->id)->with('customer')->latest()->take(5)->get() : collect();
            $recentCustomerRequests = $business ? \App\Models\CustomerQuotation::where('business_id', $business->id)->with('item')->latest()->take(5)->get() : collect();
        @endphp

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Customers') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                        <svg class="size-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $customersCount }}</p>
                <flux:button variant="ghost" size="sm" :href="route('customers')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View all') }} &rarr;</flux:button>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Fabrics') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $fabricsCount }}</p>
                <flux:button variant="ghost" size="sm" :href="route('inventory')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View inventory') }} &rarr;</flux:button>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Products') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-900/20">
                        <svg class="size-5 text-violet-600 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" x2="21" y1="6" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $productsCount }}</p>
                <flux:button variant="ghost" size="sm" :href="route('inventory')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View inventory') }} &rarr;</flux:button>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Pending Invoices') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/20">
                        <svg class="size-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $pendingInvoices }}</p>
                    <flux:button variant="ghost" size="sm" :href="route('invoices')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View invoices') }} &rarr;</flux:button>
            </div>

            {{-- Customer Requests --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Customer Requests') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-rose-50 dark:bg-rose-900/20">
                        <svg class="size-5 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $pendingCustomerRequests }}</p>
                <flux:button variant="ghost" size="sm" :href="route('customer-quotations')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View requests') }} &rarr;</flux:button>
            </div>
        </div>

        {{-- Subscription Status --}}
        @php
            $activeSub = $business ? $business->activeSubscription : null;
            $plan = $activeSub?->plan;
            if ($plan && $business) {
                $qUsage = $business->quotations()->where('created_at', '>=', $activeSub->starts_at)->count();
                $iUsage = $business->invoices()->where('created_at', '>=', $activeSub->starts_at)->count();
                $rUsage = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('business_id', $business->id))->whereNotNull('receipt_number')->where('created_at', '>=', $activeSub->starts_at)->count();
            }
        @endphp
        @if ($plan)
            <div class="mt-6 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/20">
                            <svg class="size-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </div>
                        <div>
                            <flux:heading size="sm" class="text-neutral-500">{{ __('Subscription') }}</flux:heading>
                            <p class="text-sm font-semibold text-neutral-900 dark:text-white">{{ $plan->name }} &middot; {{ ucfirst($activeSub->billing_cycle) }} @if ($activeSub->amount > 0)&middot; UGX {{ number_format($activeSub->amount) }}/{{ substr($activeSub->billing_cycle, 0, 4) }}y @endif</p>
                        </div>
                    </div>
                    <flux:button variant="ghost" size="sm" :href="route('billing')" wire:navigate class="text-xs">{{ __('Manage') }} &rarr;</flux:button>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-neutral-500">Quotations</span>
                            <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $qUsage }}{{ !$plan->isUnlimited('quotations') ? ' / ' . $plan->limit('quotations') : ' / ∞' }}</span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            @if ($plan->isUnlimited('quotations'))
                                <div class="h-full rounded-full bg-emerald-500" style="width: 100%"></div>
                            @else
                                <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ min(100, ($qUsage / max(1, $plan->limit('quotations'))) * 100) }}%"></div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-neutral-500">Invoices</span>
                            <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $iUsage }}{{ !$plan->isUnlimited('invoices') ? ' / ' . $plan->limit('invoices') : ' / ∞' }}</span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            @if ($plan->isUnlimited('invoices'))
                                <div class="h-full rounded-full bg-emerald-500" style="width: 100%"></div>
                            @else
                                <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ min(100, ($iUsage / max(1, $plan->limit('invoices'))) * 100) }}%"></div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-neutral-500">Receipts</span>
                            <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $rUsage }}{{ !$plan->isUnlimited('receipts') ? ' / ' . $plan->limit('receipts') : ' / ∞' }}</span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            @if ($plan->isUnlimited('receipts'))
                                <div class="h-full rounded-full bg-emerald-500" style="width: 100%"></div>
                            @else
                                <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ min(100, ($rUsage / max(1, $plan->limit('receipts'))) * 100) }}%"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="mt-8">
            <flux:heading size="sm" class="mb-3">{{ __('Quick Actions') }}</flux:heading>
            <div class="flex flex-wrap gap-3">
                <flux:button variant="primary" color="indigo" :href="route('quotations.create')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    {{ __('New Quotation') }}
                </flux:button>
                <flux:button variant="primary" color="emerald" :href="route('invoices.create')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                    {{ __('New Invoice') }}
                </flux:button>
                <flux:button variant="warning" :href="route('customers')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    {{ __('Add Customer') }}
                </flux:button>
                <flux:button variant="primary" color="sky" :href="route('inventory')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    {{ __('Add to Inventory') }}
                </flux:button>
                <flux:button variant="primary" color="pink" :href="route('site.index')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                    {{ __('Browse Fabrics') }}
                </flux:button>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Recent Quotations --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Recent Quotations') }}</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('quotations')" wire:navigate class="px-0 text-xs">{{ __('View all') }} &rarr;</flux:button>
                </div>
                @if ($recentQuotations->isEmpty())
                    <div class="py-8 text-center">
                        <p class="text-sm text-neutral-400">{{ __('No quotations yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @foreach ($recentQuotations as $q)
                            <div class="flex items-center justify-between py-2.5">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-neutral-900 dark:text-white">{{ $q->quotation_number }}</p>
                                    <p class="truncate text-xs text-neutral-500">{{ $q->customer?->name ?? __('Walk-in') }} &middot; {{ $q->issue_date->format('d M Y') }}</p>
                                </div>
                                <div class="ml-3 flex items-center gap-3">
                                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">UGX {{ number_format($q->total, 0) }}</span>
                                    <flux:badge :icon="match($q->status) { 'draft' => 'clock', 'sent' => 'paper-airplane', 'accepted' => 'check-badge', default => 'clock' }" :variant="match($q->status) { 'draft' => 'ghost', 'sent' => 'primary', 'accepted' => 'success', default => 'ghost' }" size="sm" class="shrink-0">{{ ucfirst($q->status) }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent Invoices --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Recent Invoices') }}</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('invoices')" wire:navigate class="px-0 text-xs">{{ __('View all') }} &rarr;</flux:button>
                </div>
                @if ($recentInvoices->isEmpty())
                    <div class="py-8 text-center">
                        <p class="text-sm text-neutral-400">{{ __('No invoices yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @foreach ($recentInvoices as $inv)
                            <div class="flex items-center justify-between py-2.5">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-neutral-900 dark:text-white">{{ $inv->invoice_number }}</p>
                                    <p class="truncate text-xs text-neutral-500">{{ $inv->customer?->name ?? __('Walk-in') }} &middot; {{ $inv->issue_date->format('d M Y') }}</p>
                                </div>
                                <div class="ml-3 flex items-center gap-3">
                                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">UGX {{ number_format($inv->total, 0) }}</span>
                                    <flux:badge :icon="match($inv->status) { 'draft' => 'clock', 'sent' => 'paper-airplane', 'paid' => 'check-circle', 'overdue' => 'exclamation-triangle', default => 'clock' }" :variant="match($inv->status) { 'draft' => 'ghost', 'sent' => 'primary', 'paid' => 'success', 'overdue' => 'warning', default => 'ghost' }" size="sm" class="shrink-0">{{ ucfirst($inv->status) }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent Customer Requests --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Recent Customer Requests') }}</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('customer-quotations')" wire:navigate class="px-0 text-xs">{{ __('View all') }} &rarr;</flux:button>
                </div>
                @if ($recentCustomerRequests->isEmpty())
                    <div class="py-8 text-center">
                        <p class="text-sm text-neutral-400">{{ __('No customer requests yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @foreach ($recentCustomerRequests as $cr)
                            <div class="flex items-center justify-between py-2.5">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-neutral-900 dark:text-white">{{ $cr->customer_name }}</p>
                                    <p class="truncate text-xs text-neutral-500">{{ $cr->item?->name ?? '—' }} &middot; {{ number_format($cr->length_meters ?: 1, 1) }}{{ $cr->item_type === 'fabric' ? 'm' : ' ' . ($cr->item->unit ?? 'units') }} &middot; {{ $cr->created_at->format('d M Y') }}</p>
                                </div>
                                <div class="ml-3 flex items-center gap-3">
                                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">UGX {{ number_format($cr->total_price, 0) }}</span>
                                    <flux:badge :variant="$cr->status === 'pending' ? 'warning' : ($cr->status === 'responded' ? 'success' : 'ghost')" size="sm" class="shrink-0">{{ ucfirst($cr->status) }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
