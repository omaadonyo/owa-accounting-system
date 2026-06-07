<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Traits\ChecksSubscriptionLimits;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Subscription & Billing')] class extends Component {
    use ChecksSubscriptionLimits;

    public ?int $selectedPlanId = null;
    public string $billingCycle = 'monthly';
    public string $paymentMethod = 'mobile_money';
    public string $mobileMoneyProvider = 'mtn';
    public ?string $paymentReference = null;
    public ?string $paymentPhone = null;
    public bool $showUpgradeModal = false;

    public function mount(): void
    {
        $business = auth()->user()->business;
        if (!$business) {
            return;
        }
        $subscription = $this->getActiveSubscription($business);
        if ($subscription) {
            $this->selectedPlanId = $subscription->plan_id;
            $this->billingCycle = $subscription->billing_cycle;
        }
    }

    public function selectPlan(int $planId): void
    {
        $business = auth()->user()->business;
        if (!$business) {
            return;
        }

        $plan = Plan::find($planId);
        if (!$plan || !$plan->is_active) {
            return;
        }

        if ($this->getActiveSubscription($business)?->plan_id === $planId) {
            return;
        }

        $this->selectedPlanId = $planId;
        $this->showUpgradeModal = true;
    }

    public function submitPayment(): void
    {
        $plan = Plan::find($this->selectedPlanId);
        if (!$plan) {
            return;
        }

        $amount = $plan->getPrice($this->billingCycle);

        if ($amount > 0) {
            $this->validate([
                'paymentMethod' => ['required', 'in:mobile_money,bank_transfer,cash'],
                'mobileMoneyProvider' => ['required_if:paymentMethod,mobile_money', 'in:mtn,airtel'],
                'paymentPhone' => ['required_if:paymentMethod,mobile_money', 'nullable', 'string', 'max:20'],
                'paymentReference' => ['nullable', 'string', 'max:255'],
            ]);
        }

        $business = auth()->user()->business;
        if (!$business) {
            return;
        }

        $now = now();

        $endsAt = match ($this->billingCycle) {
            'yearly' => $now->copy()->addYear(),
            default => $now->copy()->addMonth(),
        };

        $oldSub = $this->getActiveSubscription($business);
        if ($oldSub) {
            $oldSub->update(['status' => 'cancelled', 'cancelled_at' => $now]);
        }

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => $amount > 0 ? 'pending' : 'active',
            'billing_cycle' => $this->billingCycle,
            'amount' => $amount,
            'starts_at' => $now,
            'ends_at' => $endsAt,
            'payment_method' => $this->paymentMethod,
            'payment_reference' => $this->paymentReference,
            'paid_at' => $amount > 0 ? null : $now,
        ]);

        $this->showUpgradeModal = false;

        session()->flash('success', $amount === 0 ? 'Downgraded to Free plan.' : 'Subscription updated successfully.');

        $this->redirect(route('billing'));
    }

    public function getPlansProperty()
    {
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function getActiveSubProperty()
    {
        $business = auth()->user()->business;
        return $business ? $this->getActiveSubscription($business) : null;
    }

    public function getCurrentPlanProperty()
    {
        return $this->activeSub?->plan ?? Plan::where('slug', 'free')->first();
    }

    public function getUsageQuotationsProperty(): int
    {
        $business = auth()->user()->business;
        return $business ? $this->getUsage($business, 'quotations') : 0;
    }

    public function getUsageInvoicesProperty(): int
    {
        $business = auth()->user()->business;
        return $business ? $this->getUsage($business, 'invoices') : 0;
    }

    public function getUsageReceiptsProperty(): int
    {
        $business = auth()->user()->business;
        return $business ? $this->getUsage($business, 'receipts') : 0;
    }

    public function getSubscriptionsProperty()
    {
        $business = auth()->user()->business;
        return $business ? $business->subscriptions()->with('plan')->latest()->take(10)->get() : collect();
    }
}; ?>

<div class="mx-auto max-w-5xl">
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <flux:heading size="xl">Subscription & Billing</flux:heading>
    <flux:subheading class="mt-1">Manage your plan, view usage, and upgrade.</flux:subheading>

    @if (!$this->currentPlan)
        <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800/40 dark:bg-amber-900/20">
            <p class="text-amber-700 dark:text-amber-300">No active subscription found. Choose a plan below to get started.</p>
        </div>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <p class="text-xs font-medium uppercase tracking-wider text-neutral-500">Current Plan</p>
                <p class="mt-1 text-lg font-bold text-neutral-900 dark:text-white">{{ $this->currentPlan->name }}</p>
                @if ($this->activeSub)
                    <p class="mt-0.5 text-xs text-neutral-500">{{ ucfirst($this->activeSub->billing_cycle) }} &middot; @if ($this->activeSub->amount > 0) UGX {{ number_format($this->activeSub->amount) }}/{{ substr($this->activeSub->billing_cycle, 0, 4) }}y @else Free @endif</p>
                @endif
            </div>
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <p class="text-xs font-medium uppercase tracking-wider text-neutral-500">Quotations</p>
                <p class="mt-1 text-lg font-bold text-neutral-900 dark:text-white">{{ $this->usageQuotations }}</p>
                @if ($this->currentPlan && !$this->currentPlan->isUnlimited('quotations'))
                    <p class="mt-0.5 text-xs text-neutral-500">of {{ $this->currentPlan->limit('quotations') }}</p>
                @else
                    <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">Unlimited</p>
                @endif
            </div>
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <p class="text-xs font-medium uppercase tracking-wider text-neutral-500">Invoices</p>
                <p class="mt-1 text-lg font-bold text-neutral-900 dark:text-white">{{ $this->usageInvoices }}</p>
                @if ($this->currentPlan && !$this->currentPlan->isUnlimited('invoices'))
                    <p class="mt-0.5 text-xs text-neutral-500">of {{ $this->currentPlan->limit('invoices') }}</p>
                @else
                    <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">Unlimited</p>
                @endif
            </div>
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <p class="text-xs font-medium uppercase tracking-wider text-neutral-500">Receipts</p>
                <p class="mt-1 text-lg font-bold text-neutral-900 dark:text-white">{{ $this->usageReceipts }}</p>
                @if ($this->currentPlan && !$this->currentPlan->isUnlimited('receipts'))
                    <p class="mt-0.5 text-xs text-neutral-500">of {{ $this->currentPlan->limit('receipts') }}</p>
                @else
                    <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">Unlimited</p>
                @endif
            </div>
        </div>
    @endif

    <div class="mt-10">
        <div class="mb-6 flex items-center justify-between">
            <flux:heading>Available Plans</flux:heading>
            <div class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-white p-1 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <button wire:click="$set('billingCycle', 'monthly')" class="rounded-lg px-4 py-1.5 text-sm font-medium transition {{ $billingCycle === 'monthly' ? 'bg-indigo-600 text-white shadow-sm' : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' }}">Monthly</button>
                <button wire:click="$set('billingCycle', 'yearly')" class="rounded-lg px-4 py-1.5 text-sm font-medium transition {{ $billingCycle === 'yearly' ? 'bg-indigo-600 text-white shadow-sm' : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' }}">Yearly</button>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($this->plans as $plan)
                @php
                    $price = $plan->getPrice($billingCycle);
                    $isCurrent = $this->currentPlan && $this->currentPlan->id === $plan->id;
                    $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
                @endphp
                <div class="relative flex flex-col rounded-2xl border p-6 shadow-sm transition {{ $isCurrent ? 'border-indigo-300 bg-indigo-50/50 dark:border-indigo-500/50 dark:bg-indigo-500/5' : 'border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 hover:border-neutral-300 dark:hover:border-neutral-600' }}">
                    @if ($plan->slug === 'enterprise')
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600 px-4 py-0.5 text-xs font-semibold text-white shadow-sm">Popular</div>
                    @endif
                    @if ($isCurrent)
                        <div class="absolute right-4 top-4 rounded-full bg-indigo-100 px-2.5 py-0.5 text-[10px] font-medium text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">Current</div>
                    @endif
                    <h3 class="text-lg font-bold text-neutral-900 dark:text-white">{{ $plan->name }}</h3>
                    <p class="mt-1 text-xs text-neutral-500">{{ $plan->description }}</p>
                    <div class="mt-4 flex items-baseline gap-1">
                        @if ($price > 0)
                            <span class="text-3xl font-bold text-neutral-900 dark:text-white">UGX {{ number_format($price) }}</span>
                            <span class="text-sm text-neutral-500">/{{ $billingCycle === 'yearly' ? 'yr' : 'mo' }}</span>
                        @else
                            <span class="text-3xl font-bold text-neutral-900 dark:text-white">Free</span>
                        @endif
                    </div>
                    <ul class="mt-6 flex-1 space-y-2.5">
                        @foreach ($features as $feature)
                            <li class="flex items-start gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <svg class="mt-0.5 size-4 shrink-0 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                    <button wire:click="selectPlan({{ $plan->id }})" class="mt-6 w-full rounded-xl py-2.5 text-sm font-semibold transition {{ $isCurrent ? 'border border-neutral-300 text-neutral-400 cursor-not-allowed dark:border-neutral-600 dark:text-neutral-500' : 'bg-indigo-600 text-white hover:bg-indigo-500 shadow-sm' }}" {{ $isCurrent ? 'disabled' : '' }}>
                        @if ($isCurrent) Current Plan @elseif ($plan->slug === 'free') Downgrade to Free @else Upgrade @endif
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    @if ($this->subscriptions->count() > 0)
        <div class="mt-12">
            <flux:heading>Billing History</flux:heading>
            <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200 shadow-sm dark:border-neutral-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50 text-left dark:border-neutral-700 dark:bg-neutral-800/50">
                            <th class="px-4 py-3 font-medium text-neutral-500">Plan</th>
                            <th class="px-4 py-3 font-medium text-neutral-500">Amount</th>
                            <th class="px-4 py-3 font-medium text-neutral-500">Billing</th>
                            <th class="px-4 py-3 font-medium text-neutral-500">Status</th>
                            <th class="px-4 py-3 font-medium text-neutral-500">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->subscriptions as $sub)
                            <tr class="border-b border-neutral-200 last:border-0 dark:border-neutral-700/50">
                                <td class="px-4 py-3 text-neutral-900 dark:text-white">{{ $sub->plan?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400">UGX {{ number_format($sub->amount) }}</td>
                                <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400">{{ ucfirst($sub->billing_cycle) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{
                                        match($sub->status) {
                                            'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
                                            'expired' => 'bg-neutral-100 text-neutral-500 dark:bg-neutral-500/10 dark:text-neutral-400',
                                            default => 'bg-neutral-100 text-neutral-500 dark:bg-neutral-500/10 dark:text-neutral-400',
                                        }
                                    }}">
                                        {{ ucfirst($sub->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-neutral-500">{{ $sub->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Payment Methods Info --}}
    <div class="mt-10 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <flux:heading>Payment Methods</flux:heading>
        <flux:subheading class="mt-1">We accept the following payment methods.</flux:subheading>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/30">
                <div class="flex items-center gap-2">
                    <svg class="size-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    <span class="text-sm font-medium text-neutral-900 dark:text-white">Mobile Money</span>
                </div>
                <p class="mt-1 text-xs text-neutral-500">MTN &amp; Airtel</p>
            </div>
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/30">
                <div class="flex items-center gap-2">
                    <svg class="size-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    <span class="text-sm font-medium text-neutral-900 dark:text-white">Bank Transfer</span>
                </div>
                <p class="mt-1 text-xs text-neutral-500">Manual confirmation</p>
            </div>
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/30">
                <div class="flex items-center gap-2">
                    <svg class="size-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span class="text-sm font-medium text-neutral-900 dark:text-white">Flutterwave</span>
                </div>
                <p class="mt-1 text-xs text-neutral-500">Coming soon</p>
            </div>
        </div>
    </div>

    {{-- Upgrade Modal --}}
    <flux:modal wire:model="showUpgradeModal" class="w-full max-w-lg">
        <div class="p-1">
            @php
                $selectedPlan = $this->plans->firstWhere('id', $selectedPlanId);
                $selectedPrice = $selectedPlan?->getPrice($billingCycle) ?? 0;
            @endphp
            @if ($selectedPlan)
                <flux:heading size="lg">{{ $selectedPlan->slug === 'free' ? 'Downgrade to Free' : 'Upgrade to ' . $selectedPlan->name }}</flux:heading>
                <flux:subheading class="mt-1">
                    @if ($selectedPrice > 0)
                        UGX {{ number_format($selectedPrice) }}/{{ $billingCycle === 'yearly' ? 'year' : 'month' }}
                    @else
                        Free plan — no payment required.
                    @endif
                </flux:subheading>

                @if ($selectedPrice > 0)
                    <div class="mt-6 space-y-4">
                        <div>
                            <flux:label>Payment Method</flux:label>
                            <div class="custom-select relative mt-1">
                                <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-indigo-400">
                                    <span data-cs-display>Mobile Money</span>
                                    <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                                    <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                                        <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                                    </div>
                                    <div data-cs-options class="max-h-48 overflow-y-auto py-1">
                                        <button type="button" data-cs-option data-cs-value="mobile_money" data-cs-label="Mobile Money" class="cs-selected flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-neutral-300 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-300">Mobile Money</button>
                                        <button type="button" data-cs-option data-cs-value="bank_transfer" data-cs-label="Bank Transfer" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-neutral-300 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-300">Bank Transfer</button>
                                        <button type="button" data-cs-option data-cs-value="cash" data-cs-label="Cash" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-neutral-300 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-300">Cash</button>
                                    </div>
                                </div>
                                <select wire:model="paymentMethod" class="sr-only">
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                        </div>

                        @if ($paymentMethod === 'mobile_money')
                            <div>
                                <flux:label>Mobile Money Provider</flux:label>
                                <div class="custom-select relative mt-1">
                                    <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-indigo-400">
                                        <span data-cs-display>MTN Mobile Money</span>
                                        <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                                        <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                                            <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                                        </div>
                                        <div data-cs-options class="max-h-48 overflow-y-auto py-1">
                                            <button type="button" data-cs-option data-cs-value="mtn" data-cs-label="MTN Mobile Money" class="cs-selected flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-neutral-300 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-300">MTN Mobile Money</button>
                                            <button type="button" data-cs-option data-cs-value="airtel" data-cs-label="Airtel Money" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-neutral-300 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-300">Airtel Money</button>
                                        </div>
                                    </div>
                                    <select wire:model="mobileMoneyProvider" class="sr-only">
                                        <option value="mtn">MTN Mobile Money</option>
                                        <option value="airtel">Airtel Money</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <flux:label>Phone Number</flux:label>
                                <flux:input wire:model="paymentPhone" type="text" placeholder="e.g. 0772000000" class="mt-1" />
                            </div>
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-300">
                                Send UGX {{ number_format($selectedPrice) }} to <strong>0772000000</strong> (MTN) or <strong>0772000001</strong> (Airtel). Enter the transaction ID below.
                            </div>
                        @elseif ($paymentMethod === 'bank_transfer')
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-300">
                                Transfer UGX {{ number_format($selectedPrice) }} to:<br>
                                <strong>Bank:</strong> Centenary Bank<br>
                                <strong>Account Name:</strong> Akatabo Systems Ltd<br>
                                <strong>Account Number:</strong> 1234567890<br>
                                Enter the reference number below after payment.
                            </div>
                        @else
                            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-xs text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800/30 dark:text-neutral-400">
                                Pay UGX {{ number_format($selectedPrice) }} in person or arrange pickup. Enter a reference if available.
                            </div>
                        @endif

                        <div>
                            <flux:label>{{ $paymentMethod === 'bank_transfer' ? 'Transaction Reference' : ($paymentMethod === 'mobile_money' ? 'Transaction ID' : 'Reference (optional)') }}</flux:label>
                            <flux:input wire:model="paymentReference" type="text" placeholder="e.g. TXN123456" class="mt-1" />
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700 dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                        Switching to the Free plan will downgrade immediately. You'll lose access to premium features once the current billing period ends.
                    </div>
                @endif

                <div class="mt-6 flex items-center justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('showUpgradeModal', false)">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="submitPayment">
                        @if ($selectedPlan->slug === 'free') Confirm Downgrade
                        @elseif ($selectedPrice > 0) Submit Payment
                        @else Activate
                        @endif
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
