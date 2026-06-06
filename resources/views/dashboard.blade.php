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
            $recentQuotations = $business ? \App\Models\Quotation::where('business_id', $business->id)->with('customer')->latest()->take(5)->get() : collect();
            $recentInvoices = $business ? \App\Models\Invoice::where('business_id', $business->id)->with('customer')->latest()->take(5)->get() : collect();
        @endphp

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Customers') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                        <svg class="size-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $customersCount }}</p>
                <flux:button variant="ghost" size="sm" :href="route('customers')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View all') }} &rarr;</flux:button>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Fabrics') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $fabricsCount }}</p>
                <flux:button variant="ghost" size="sm" :href="route('inventory')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View inventory') }} &rarr;</flux:button>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Products') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-900/20">
                        <svg class="size-5 text-violet-600 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" x2="21" y1="6" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $productsCount }}</p>
                <flux:button variant="ghost" size="sm" :href="route('inventory')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View inventory') }} &rarr;</flux:button>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="text-neutral-500">{{ __('Pending Invoices') }}</flux:heading>
                    <div class="flex size-9 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/20">
                        <svg class="size-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-neutral-900 dark:text-white">{{ $pendingInvoices }}</p>
                <flux:button variant="ghost" size="sm" :href="route('invoices')" wire:navigate class="mt-2 px-0 text-xs">{{ __('View invoices') }} &rarr;</flux:button>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="mt-8">
            <flux:heading size="sm" class="mb-3">{{ __('Quick Actions') }}</flux:heading>
            <div class="flex flex-wrap gap-3">
                <flux:button variant="primary" :href="route('quotations.create')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                    {{ __('New Quotation') }}
                </flux:button>
                <flux:button variant="primary" :href="route('invoices.create')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                    {{ __('New Invoice') }}
                </flux:button>
                <flux:button variant="primary" :href="route('customers')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                    {{ __('Add Customer') }}
                </flux:button>
                <flux:button variant="primary" :href="route('inventory')" wire:navigate>
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                    {{ __('Add to Inventory') }}
                </flux:button>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Recent Quotations --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
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
                                    <flux:badge :variant="match($q->status) { 'draft' => 'ghost', 'sent' => 'primary', 'accepted' => 'success', default => 'ghost' }" size="sm" class="shrink-0">{{ ucfirst($q->status) }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent Invoices --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
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
                                    <flux:badge :variant="match($inv->status) { 'draft' => 'ghost', 'sent' => 'primary', 'paid' => 'success', 'overdue' => 'warning', default => 'ghost' }" size="sm" class="shrink-0">{{ ucfirst($inv->status) }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
