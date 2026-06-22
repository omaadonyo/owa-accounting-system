<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto max-w-7xl">
        @php
            $business = currentBusiness();
            $customersCount = $business?->customers()->count() ?? 0;
            $fabricsCount = $business?->fabrics()->count() ?? 0;
            $productsCount = $business?->productsServices()->count() ?? 0;
            $quotationsCount = $business ? \App\Models\Quotation::where('business_id', $business->id)->count() : 0;
            $invoicesCount = $business ? \App\Models\Invoice::where('business_id', $business->id)->count() : 0;
            $pendingInvoices = $business ? \App\Models\Invoice::where('business_id', $business->id)->whereIn('status', ['draft', 'sent'])->count() : 0;
            $pendingCustomerRequests = $business ? \App\Models\CustomerQuotation::where('business_id', $business->id)->where('status', 'pending')->count() : 0;
            $totalRevenue = $business ? \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('business_id', $business->id))->sum('amount') : 0;
            $totalInvoiced = $business ? \App\Models\Invoice::where('business_id', $business->id)->sum('total') : 0;
            $paidInvoices = $business ? \App\Models\Invoice::where('business_id', $business->id)->where('status', 'paid')->count() : 0;
            $totalPayments = $business ? \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('business_id', $business->id))->count() : 0;
            $convertedQuotations = $business ? \App\Models\Quotation::where('business_id', $business->id)->where('status', 'converted')->count() : 0;
            $conversionRate = $quotationsCount > 0 ? round(($convertedQuotations / $quotationsCount) * 100, 1) : 0;
            $recentQuotations = $business ? \App\Models\Quotation::where('business_id', $business->id)->with('customer')->latest()->take(5)->get() : collect();
            $recentInvoices = $business ? \App\Models\Invoice::where('business_id', $business->id)->with('customer')->latest()->take(5)->get() : collect();
            $recentCustomerRequests = $business ? \App\Models\CustomerQuotation::where('business_id', $business->id)->with('item')->latest()->take(5)->get() : collect();
        @endphp

        <div class="mb-8">
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Overview of your business activity.') }}</flux:subheading>
        </div>

        {{-- KPI Cards Row 1 --}}
        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm dark:border-emerald-800/30 dark:from-emerald-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-emerald-100/60 dark:bg-emerald-900/30">
                    <svg class="size-6 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Total Revenue') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($totalRevenue, 0) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $totalPayments }} {{ __('payments') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm dark:border-blue-800/30 dark:from-blue-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-blue-100/60 dark:bg-blue-900/30">
                    <svg class="size-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/></svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">{{ __('Invoiced') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($totalInvoiced, 0) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ $invoicesCount }} {{ __('invoices') }}</span>
                    <span class="text-neutral-300 dark:text-neutral-600">|</span>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $paidInvoices }} {{ __('paid') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm dark:border-violet-800/30 dark:from-violet-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-violet-100/60 dark:bg-violet-900/30">
                    <svg class="size-6 text-violet-600 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">{{ __('Customers') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $customersCount }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <a href="{{ route('customers') }}" wire:navigate class="rounded-full bg-violet-100 px-2 py-0.5 font-medium text-violet-700 transition hover:bg-violet-200 dark:bg-violet-900/40 dark:text-violet-300 dark:hover:bg-violet-900/60">{{ __('View all') }} &rarr;</a>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm dark:border-amber-800/30 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-amber-100/60 dark:bg-amber-900/30">
                    <svg class="size-6 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('Pending') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $pendingInvoices }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ __('invoices') }}</span>
                    <span class="text-neutral-300 dark:text-neutral-600">|</span>
                    <span class="rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ $pendingCustomerRequests }} {{ __('requests') }}</span>
                </div>
            </div>
        </div>

        {{-- KPI Cards Row 2 --}}
        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative overflow-hidden rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm dark:border-rose-800/30 dark:from-rose-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-rose-100/60 dark:bg-rose-900/30">
                    <svg class="size-6 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" x2="21" y1="6" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">{{ __('Products') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $productsCount }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ $fabricsCount }} {{ __('fabrics') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm dark:border-sky-800/30 dark:from-sky-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-sky-100/60 dark:bg-sky-900/30">
                    <svg class="size-6 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-600 dark:text-sky-400">{{ __('Quotations') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $quotationsCount }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-sky-100 px-2 py-0.5 font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ $quotationsCount }} {{ __('total') }}</span>
                    <span class="text-neutral-300 dark:text-neutral-600">|</span>
                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $convertedQuotations }} {{ __('converted') }}</span>
                </div>
            </div>

            @php $convBarColor = $conversionRate >= 60 ? 'bg-emerald-500' : ($conversionRate >= 30 ? 'bg-amber-500' : 'bg-rose-500'); @endphp
            <div class="relative overflow-hidden rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-5 shadow-sm dark:border-indigo-800/30 dark:from-indigo-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-indigo-100/60 dark:bg-indigo-900/30">
                    <svg class="size-6 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Conversion Rate') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $conversionRate }}%</p>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                    <div class="h-full rounded-full transition-all duration-500 {{ $convBarColor }}" style="width: {{ $conversionRate }}%"></div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-5 shadow-sm dark:border-teal-800/30 dark:from-teal-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-teal-100/60 dark:bg-teal-900/30">
                    <svg class="size-6 text-teal-600 dark:text-teal-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-600 dark:text-teal-400">{{ __('Customer Requests') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $pendingCustomerRequests }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <a href="{{ route('customer-quotations') }}" wire:navigate class="rounded-full bg-teal-100 px-2 py-0.5 font-medium text-teal-700 transition hover:bg-teal-200 dark:bg-teal-900/40 dark:text-teal-300 dark:hover:bg-teal-900/60">{{ __('View requests') }} &rarr;</a>
                </div>
            </div>
        </div>

        {{-- Subscription Status --}}
        @php
            $activeSub = auth()->user()?->subscription;
            $plan = $activeSub?->plan;
            if ($plan) {
                $ownedIds = auth()->user()->ownedBusinesses()->pluck('id')->toArray();
                $qUsage = \App\Models\Quotation::whereIn('business_id', $ownedIds)->where('created_at', '>=', $activeSub->starts_at)->count();
                $iUsage = \App\Models\Invoice::whereIn('business_id', $ownedIds)->where('created_at', '>=', $activeSub->starts_at)->count();
                $rUsage = \App\Models\Payment::whereHas('invoice', fn($q) => $q->whereIn('business_id', $ownedIds))->whereNotNull('receipt_number')->where('created_at', '>=', $activeSub->starts_at)->count();
            }
        @endphp
        @if ($plan)
            <div class="mb-6 overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50/50 to-white shadow-sm dark:border-amber-800/30 dark:from-amber-950/20 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-amber-100 px-5 py-4 dark:border-amber-800/20">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                            <svg class="size-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('Subscription') }}</p>
                            <p class="text-sm font-bold text-neutral-900 dark:text-white">{{ $plan->name }} &middot; {{ ucfirst($activeSub->billing_cycle) }} @if ($activeSub->amount > 0)&middot; {{ formatCurrency($activeSub->amount, 0) }}/{{ substr($activeSub->billing_cycle, 0, 4) }}y @endif</p>
                        </div>
                    </div>
                    <flux:button variant="ghost" size="sm" :href="route('billing')" wire:navigate class="text-xs">{{ __('Manage') }} &rarr;</flux:button>
                </div>
                <div class="grid gap-5 p-5 sm:grid-cols-3">
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-xs">
                            <span class="font-medium text-neutral-600 dark:text-neutral-400">{{ __('Quotations') }}</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $qUsage }}{{ !$plan->isUnlimited('quotations') ? ' / ' . $plan->limit('quotations') : ' / ∞' }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            @if ($plan->isUnlimited('quotations'))
                                <div class="h-full rounded-full bg-emerald-500" style="width: 100%"></div>
                            @else
                                <div class="h-full rounded-full bg-sky-500 transition-all" style="width: {{ min(100, ($qUsage / max(1, $plan->limit('quotations'))) * 100) }}%"></div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-xs">
                            <span class="font-medium text-neutral-600 dark:text-neutral-400">{{ __('Invoices') }}</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $iUsage }}{{ !$plan->isUnlimited('invoices') ? ' / ' . $plan->limit('invoices') : ' / ∞' }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            @if ($plan->isUnlimited('invoices'))
                                <div class="h-full rounded-full bg-emerald-500" style="width: 100%"></div>
                            @else
                                <div class="h-full rounded-full bg-violet-500 transition-all" style="width: {{ min(100, ($iUsage / max(1, $plan->limit('invoices'))) * 100) }}%"></div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-xs">
                            <span class="font-medium text-neutral-600 dark:text-neutral-400">{{ __('Receipts') }}</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $rUsage }}{{ !$plan->isUnlimited('receipts') ? ' / ' . $plan->limit('receipts') : ' / ∞' }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            @if ($plan->isUnlimited('receipts'))
                                <div class="h-full rounded-full bg-emerald-500" style="width: 100%"></div>
                            @else
                                <div class="h-full rounded-full bg-amber-500 transition-all" style="width: {{ min(100, ($rUsage / max(1, $plan->limit('receipts'))) * 100) }}%"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="mb-6">
            <div class="mb-4 flex items-center gap-2">
                <div class="flex size-7 items-center justify-center rounded-md bg-neutral-100 dark:bg-neutral-800">
                    <svg class="size-4 text-neutral-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </div>
                <flux:heading size="sm" class="text-neutral-600 dark:text-neutral-400">{{ __('Quick Actions') }}</flux:heading>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('quotations.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white px-4 py-2.5 text-sm font-semibold text-sky-700 shadow-sm transition hover:from-sky-100 hover:shadow-md dark:border-sky-800/40 dark:from-sky-950/30 dark:to-[oklch(0.21_0.02_320.19)] dark:text-sky-300 dark:hover:from-sky-900/40">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    {{ __('New Quotation') }}
                </a>
                <a href="{{ route('invoices.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:from-emerald-100 hover:shadow-md dark:border-emerald-800/40 dark:from-emerald-950/30 dark:to-[oklch(0.21_0.02_320.19)] dark:text-emerald-300 dark:hover:from-emerald-900/40">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                    {{ __('New Invoice') }}
                </a>
                <a href="{{ route('customers') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white px-4 py-2.5 text-sm font-semibold text-violet-700 shadow-sm transition hover:from-violet-100 hover:shadow-md dark:border-violet-800/40 dark:from-violet-950/30 dark:to-[oklch(0.21_0.02_320.19)] dark:text-violet-300 dark:hover:from-violet-900/40">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    {{ __('Add Customer') }}
                </a>
                <a href="{{ route('inventory') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition hover:from-rose-100 hover:shadow-md dark:border-rose-800/40 dark:from-rose-950/30 dark:to-[oklch(0.21_0.02_320.19)] dark:text-rose-300 dark:hover:from-rose-900/40">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    {{ __('Add to Inventory') }}
                </a>
                <a href="{{ route('site.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white px-4 py-2.5 text-sm font-semibold text-amber-700 shadow-sm transition hover:from-amber-100 hover:shadow-md dark:border-amber-800/40 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)] dark:text-amber-300 dark:hover:from-amber-900/40">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                    {{ __('Browse Fabrics') }}
                </a>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Recent Quotations --}}
            <div class="overflow-hidden rounded-xl border border-sky-200 bg-white shadow-sm dark:border-sky-800/30 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-sky-100 px-4 py-3.5 dark:border-sky-800/20">
                    <div class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-md bg-sky-100 dark:bg-sky-900/30">
                            <svg class="size-4 text-sky-600 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <flux:heading size="sm">{{ __('Recent Quotations') }}</flux:heading>
                    </div>
                    <a href="{{ route('quotations') }}" wire:navigate class="text-xs font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400">{{ __('View all') }} &rarr;</a>
                </div>
                @if ($recentQuotations->isEmpty())
                    <div class="flex flex-col items-center py-8">
                        <svg class="size-8 text-neutral-300 dark:text-neutral-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <p class="mt-2 text-sm text-neutral-400">{{ __('No quotations yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-sky-100/50 dark:divide-sky-800/20">
                        @foreach ($recentQuotations as $q)
                            <div class="flex items-center justify-between px-4 py-3 transition hover:bg-sky-50/50 dark:hover:bg-sky-950/20">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-neutral-900 dark:text-white">{{ $q->quotation_number }}</p>
                                    <p class="truncate text-xs text-neutral-500">{{ $q->customer?->name ?? __('Walk-in') }} &middot; {{ $q->issue_date->format('d M Y') }}</p>
                                </div>
                                <div class="ml-3 flex items-center gap-2">
                                    <span class="text-sm font-bold text-neutral-900 dark:text-white">{{ formatCurrency($q->total, 0) }}</span>
                                    <flux:badge variant="pill" size="sm" :color="match($q->status) { 'draft' => 'neutral', 'sent' => 'blue', 'accepted' => 'lime', 'converted' => 'indigo', default => 'neutral' }" :icon="match($q->status) { 'draft' => 'clock', 'sent' => 'paper-airplane', 'accepted' => 'check-badge', 'converted' => 'arrow-right-circle', default => 'clock' }">{{ ucfirst($q->status) }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent Invoices --}}
            <div class="overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-800/30 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-emerald-100 px-4 py-3.5 dark:border-emerald-800/20">
                    <div class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-md bg-emerald-100 dark:bg-emerald-900/30">
                            <svg class="size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                        </div>
                        <flux:heading size="sm">{{ __('Recent Invoices') }}</flux:heading>
                    </div>
                    <a href="{{ route('invoices') }}" wire:navigate class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">{{ __('View all') }} &rarr;</a>
                </div>
                @if ($recentInvoices->isEmpty())
                    <div class="flex flex-col items-center py-8">
                        <svg class="size-8 text-neutral-300 dark:text-neutral-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/></svg>
                        <p class="mt-2 text-sm text-neutral-400">{{ __('No invoices yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-emerald-100/50 dark:divide-emerald-800/20">
                        @foreach ($recentInvoices as $inv)
                            <div class="flex items-center justify-between px-4 py-3 transition hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-neutral-900 dark:text-white">{{ $inv->invoice_number }}</p>
                                    <p class="truncate text-xs text-neutral-500">{{ $inv->customer?->name ?? __('Walk-in') }} &middot; {{ $inv->issue_date->format('d M Y') }}</p>
                                </div>
                                <div class="ml-3 flex items-center gap-2">
                                    <span class="text-sm font-bold text-neutral-900 dark:text-white">{{ formatCurrency($inv->total, 0) }}</span>
                                    <flux:badge variant="pill" size="sm" :color="match($inv->status) { 'draft' => 'neutral', 'sent' => 'blue', 'paid' => 'lime', 'overdue' => 'red', default => 'neutral' }" :icon="match($inv->status) { 'draft' => 'clock', 'sent' => 'paper-airplane', 'paid' => 'check-circle', 'overdue' => 'exclamation-triangle', default => 'clock' }">{{ ucfirst($inv->status) }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent Customer Requests --}}
            <div class="overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm dark:border-violet-800/30 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-violet-100 px-4 py-3.5 dark:border-violet-800/20">
                    <div class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-md bg-violet-100 dark:bg-violet-900/30">
                            <svg class="size-4 text-violet-600 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <flux:heading size="sm">{{ __('Customer Requests') }}</flux:heading>
                    </div>
                    <a href="{{ route('customer-quotations') }}" wire:navigate class="text-xs font-medium text-violet-600 hover:text-violet-700 dark:text-violet-400">{{ __('View all') }} &rarr;</a>
                </div>
                @if ($recentCustomerRequests->isEmpty())
                    <div class="flex flex-col items-center py-8">
                        <svg class="size-8 text-neutral-300 dark:text-neutral-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <p class="mt-2 text-sm text-neutral-400">{{ __('No customer requests yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-violet-100/50 dark:divide-violet-800/20">
                        @foreach ($recentCustomerRequests as $cr)
                            <div class="flex items-center justify-between px-4 py-3 transition hover:bg-violet-50/50 dark:hover:bg-violet-950/20">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-neutral-900 dark:text-white">{{ $cr->customer_name }}</p>
                                    <p class="truncate text-xs text-neutral-500">{{ $cr->item?->name ?? '—' }} &middot; {{ number_format($cr->length_meters ?: 1, 1) }}{{ $cr->item_type === 'fabric' ? 'm' : ' ' . ($cr->item->unit ?? 'units') }} &middot; {{ $cr->created_at->format('d M Y') }}</p>
                                </div>
                                <div class="ml-3 flex items-center gap-2">
                                    <span class="text-sm font-bold text-neutral-900 dark:text-white">{{ formatCurrency($cr->total_price, 0) }}</span>
                                    <flux:badge variant="pill" size="sm" :color="$cr->status === 'pending' ? 'amber' : ($cr->status === 'responded' ? 'lime' : 'neutral')" :icon="$cr->status === 'pending' ? 'clock' : ($cr->status === 'responded' ? 'check-circle' : 'clock')">{{ ucfirst($cr->status) }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
