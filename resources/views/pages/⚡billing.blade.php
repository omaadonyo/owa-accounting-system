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
        $subscription = $this->getActiveSubscription();
        if ($subscription) {
            $this->selectedPlanId = $subscription->plan_id;
            $this->billingCycle = $subscription->billing_cycle;
        }
    }

    public function selectPlan(int $planId): void
    {
        $plan = Plan::find($planId);
        if (!$plan || !$plan->is_active) {
            return;
        }

        if ($this->getActiveSubscription()?->plan_id === $planId) {
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

        $now = now();

        $endsAt = match ($this->billingCycle) {
            'yearly' => $now->copy()->addYear(),
            default => $now->copy()->addMonth(),
        };

        $oldSub = $this->getActiveSubscription();
        if ($oldSub) {
            $oldSub->update(['status' => 'cancelled', 'cancelled_at' => $now]);
        }

        Subscription::create([
            'user_id' => auth()->id(),
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
        return $this->getActiveSubscription();
    }

    public function getCurrentPlanProperty()
    {
        return $this->activeSub?->plan ?? Plan::where('slug', 'free')->first();
    }

    public function getUsageQuotationsProperty(): int
    {
        return $this->getUsage(feature: 'quotations');
    }

    public function getUsageInvoicesProperty(): int
    {
        return $this->getUsage(feature: 'invoices');
    }

    public function getUsageReceiptsProperty(): int
    {
        return $this->getUsage(feature: 'receipts');
    }

    public function getUsageBusinessesProperty(): int
    {
        $user = auth()->user();
        return $user ? $user->businesses()->count() : 0;
    }

    public function getSubscriptionsProperty()
    {
        return auth()->user()->subscriptions()->with('plan')->latest()->take(10)->get();
    }
}; ?>

<div class="mx-auto max-w-5xl">
    @if (session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-4 text-sm text-emerald-700 shadow-sm dark:border-emerald-800/40 dark:from-emerald-950/30 dark:to-[oklch(0.21_0.02_320.19)] dark:text-emerald-300">
            <flux:icon name="check-circle" variant="solid" class="size-5 shrink-0 text-emerald-500" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="mb-8 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Subscription & Billing</flux:heading>
            <flux:subheading class="mt-1">Manage your plan, view usage, and upgrade.</flux:subheading>
        </div>
        <div class="flex items-center gap-2 text-xs text-neutral-400">
            <flux:icon name="calendar-days" class="size-3.5" />
            <span>{{ now()->format('l, d F Y') }}</span>
        </div>
    </div>

    @if (!$this->currentPlan)
        <div class="mb-8 rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 to-white p-6 shadow-sm dark:border-amber-800/40 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-triangle" variant="solid" class="size-6 text-amber-500" />
                <p class="text-sm font-medium text-amber-700 dark:text-amber-300">No active subscription found. Choose a plan below to get started.</p>
            </div>
        </div>
    @else
        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Current Plan --}}
            <div class="relative overflow-hidden rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-5 shadow-sm dark:border-indigo-800/30 dark:from-indigo-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-indigo-100/60 dark:bg-indigo-900/30">
                    <flux:icon name="credit-card" variant="solid" class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Current Plan</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $this->currentPlan->name }}</p>
                @if ($this->activeSub)
                    <div class="mt-2 flex items-center gap-2 text-xs">
                        <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ ucfirst($this->activeSub->billing_cycle) }}</span>
                        <span class="text-neutral-400">{{ $this->activeSub->amount > 0 ? formatCurrency($this->activeSub->amount, 0).'/'.substr($this->activeSub->billing_cycle, 0, 4).'y' : 'Free' }}</span>
                    </div>
                @endif
            </div>

            {{-- Usage: Quotations --}}
            <div class="relative overflow-hidden rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm dark:border-sky-800/30 dark:from-sky-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-sky-100/60 dark:bg-sky-900/30">
                    <flux:icon name="document-text" variant="solid" class="size-6 text-sky-600 dark:text-sky-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-600 dark:text-sky-400">Quotations</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $this->usageQuotations }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($this->currentPlan && !$this->currentPlan->isUnlimited('quotations'))
                        @php $qPct = $this->currentPlan->limit('quotations') > 0 ? round(($this->usageQuotations / $this->currentPlan->limit('quotations')) * 100) : 0; @endphp
                        <span class="rounded-full bg-sky-100 px-2.5 py-0.5 font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">of {{ $this->currentPlan->limit('quotations') }}</span>
                        <div class="flex-1 h-1.5 rounded-full bg-sky-100 dark:bg-sky-900/30"><div class="h-full rounded-full bg-sky-500 transition-all" style="width: {{ min($qPct, 100) }}%"></div></div>
                    @else
                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Unlimited</span>
                    @endif
                </div>
            </div>

            {{-- Usage: Businesses --}}
            <div class="relative overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm dark:border-violet-800/30 dark:from-violet-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-violet-100/60 dark:bg-violet-900/30">
                    <flux:icon name="building-storefront" variant="solid" class="size-6 text-violet-600 dark:text-violet-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">Businesses</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $this->usageBusinesses }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($this->currentPlan && !$this->currentPlan->isUnlimited('businesses'))
                        @php $bPct = $this->currentPlan->limit('businesses') > 0 ? round(($this->usageBusinesses / $this->currentPlan->limit('businesses')) * 100) : 0; @endphp
                        <span class="rounded-full bg-violet-100 px-2.5 py-0.5 font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">of {{ $this->currentPlan->limit('businesses') }}</span>
                        <div class="flex-1 h-1.5 rounded-full bg-violet-100 dark:bg-violet-900/30"><div class="h-full rounded-full bg-violet-500 transition-all" style="width: {{ min($bPct, 100) }}%"></div></div>
                    @else
                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Unlimited</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
            {{-- Usage: Invoices --}}
            <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm dark:border-amber-800/30 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-amber-100/60 dark:bg-amber-900/30">
                    <flux:icon name="document-text" variant="solid" class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Invoices</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $this->usageInvoices }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($this->currentPlan && !$this->currentPlan->isUnlimited('invoices'))
                        @php $iPct = $this->currentPlan->limit('invoices') > 0 ? round(($this->usageInvoices / $this->currentPlan->limit('invoices')) * 100) : 0; @endphp
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">of {{ $this->currentPlan->limit('invoices') }}</span>
                        <div class="flex-1 h-1.5 rounded-full bg-amber-100 dark:bg-amber-900/30"><div class="h-full rounded-full bg-amber-500 transition-all" style="width: {{ min($iPct, 100) }}%"></div></div>
                    @else
                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Unlimited</span>
                    @endif
                </div>
            </div>

            {{-- Usage: Receipts --}}
            <div class="relative overflow-hidden rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm dark:border-rose-800/30 dark:from-rose-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-rose-100/60 dark:bg-rose-900/30">
                    <flux:icon name="receipt-percent" variant="solid" class="size-6 text-rose-600 dark:text-rose-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">Receipts</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $this->usageReceipts }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($this->currentPlan && !$this->currentPlan->isUnlimited('receipts'))
                        @php $rPct = $this->currentPlan->limit('receipts') > 0 ? round(($this->usageReceipts / $this->currentPlan->limit('receipts')) * 100) : 0; @endphp
                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">of {{ $this->currentPlan->limit('receipts') }}</span>
                        <div class="flex-1 h-1.5 rounded-full bg-rose-100 dark:bg-rose-900/30"><div class="h-full rounded-full bg-rose-500 transition-all" style="width: {{ min($rPct, 100) }}%"></div></div>
                    @else
                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Unlimited</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="mt-10">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                    <flux:icon name="square-3-stack-3d" class="size-4 text-indigo-600 dark:text-indigo-400" />
                </div>
                <flux:heading>Available Plans</flux:heading>
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-white p-1 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
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
                <div class="relative flex flex-col rounded-2xl border p-6 shadow-sm transition-all duration-200 {{ $isCurrent ? 'border-indigo-300 bg-gradient-to-b from-indigo-50/80 to-white shadow-indigo-200/30 dark:border-indigo-500/50 dark:from-indigo-950/20 dark:to-[oklch(0.21_0.02_320.19)]' : 'border-neutral-200 bg-white hover:border-indigo-200 hover:shadow-indigo-100/20 dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)] dark:hover:border-indigo-700/50' }}">
                    @if ($plan->slug === 'enterprise')
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600 px-4 py-0.5 text-xs font-semibold text-white shadow-sm">Popular</div>
                    @endif
                    @if ($isCurrent)
                        <div class="absolute right-4 top-4 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 flex items-center gap-1">
                            <flux:icon name="check-circle" class="size-3" /> Current
                        </div>
                    @endif
                    <h3 class="text-lg font-bold text-neutral-900 dark:text-white">{{ $plan->name }}</h3>
                    <p class="mt-1 text-xs text-neutral-500">{{ $plan->description }}</p>
                    <div class="mt-4 flex items-baseline gap-1">
                        @if ($price > 0)
                            <span class="text-3xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($price, 0) }}</span>
                            <span class="text-sm text-neutral-500">/{{ $billingCycle === 'yearly' ? 'yr' : 'mo' }}</span>
                        @else
                            <span class="text-3xl font-bold tracking-tight text-neutral-900 dark:text-white">Free</span>
                        @endif
                    </div>
                    <ul class="mt-6 flex-1 space-y-2.5">
                        <li class="flex items-start gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                            <flux:icon name="check" class="mt-0.5 size-4 shrink-0 text-emerald-500" />
                            @if ($plan->isUnlimited('businesses'))
                                Unlimited businesses
                            @else
                                Up to {{ $plan->limit('businesses') }} businesses
                            @endif
                        </li>
                        @foreach ($features as $feature)
                            <li class="flex items-start gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <flux:icon name="check" class="mt-0.5 size-4 shrink-0 text-emerald-500" />
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                    <button wire:click="selectPlan({{ $plan->id }})" class="mt-6 w-full rounded-xl py-2.5 text-sm font-semibold transition-all {{ $isCurrent ? 'border border-neutral-300 bg-neutral-50 text-neutral-400 cursor-not-allowed dark:border-neutral-600 dark:bg-neutral-800/50 dark:text-neutral-500' : 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white hover:from-indigo-500 hover:to-indigo-400 shadow-sm hover:shadow-md' }}" {{ $isCurrent ? 'disabled' : '' }}>
                        @if ($isCurrent) Current Plan
                        @elseif ($plan->slug === 'free') Downgrade to Free
                        @else Upgrade
                        @endif
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    @if ($this->subscriptions->count() > 0)
        <div class="mt-12">
            <div class="mb-4 flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900/30">
                    <flux:icon name="clock" class="size-4 text-sky-600 dark:text-sky-400" />
                </div>
                <flux:heading>Billing History</flux:heading>
            </div>
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/80 text-left dark:border-neutral-700/50 dark:bg-neutral-800/30">
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">Plan</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">Amount</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">Billing</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">Status</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                            @foreach ($this->subscriptions as $sub)
                                <tr class="transition hover:bg-neutral-50 dark:hover:bg-white/5">
                                    <td class="px-5 py-3.5 font-medium text-neutral-900 dark:text-white">{{ $sub->plan?->name ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-neutral-700 dark:text-neutral-300">{{ formatCurrency($sub->amount, 0) }}</td>
                                    <td class="px-5 py-3.5 text-neutral-500">{{ ucfirst($sub->billing_cycle) }}</td>
                                    <td class="px-5 py-3.5">
                                        <flux:badge variant="pill" size="sm"
                                            :color="match($sub->status) { 'active' => 'green', 'pending' => 'amber', 'cancelled' => 'red', 'expired' => 'neutral', default => 'neutral' }"
                                            :icon="match($sub->status) { 'active' => 'check-circle', 'pending' => 'clock', 'cancelled' => 'x-circle', 'expired' => 'exclamation-triangle', default => 'clock' }"
                                        >
                                            {{ ucfirst($sub->status) }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-5 py-3.5 text-neutral-500">{{ $sub->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Payment Methods Info --}}
    <div class="mt-10">
        <div class="mb-4 flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                <flux:icon name="credit-card" class="size-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <flux:heading>Payment Methods</flux:heading>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm dark:border-emerald-800/30 dark:from-emerald-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center gap-2">
                    <flux:icon name="device-phone-mobile" class="size-5 text-emerald-600 dark:text-emerald-400" />
                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">Mobile Money</span>
                </div>
                <p class="mt-1 text-xs text-neutral-500">MTN &amp; Airtel</p>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm dark:border-amber-800/30 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center gap-2">
                    <flux:icon name="building-library" class="size-5 text-amber-600 dark:text-amber-400" />
                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">Bank Transfer</span>
                </div>
                <p class="mt-1 text-xs text-neutral-500">Manual confirmation</p>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm dark:border-violet-800/30 dark:from-violet-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center gap-2">
                    <flux:icon name="credit-card" class="size-5 text-violet-600 dark:text-violet-400" />
                    <span class="text-sm font-semibold text-neutral-900 dark:text-white">Flutterwave</span>
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
                        {{ formatCurrency($selectedPrice, 0) }}/{{ $billingCycle === 'yearly' ? 'year' : 'month' }}
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
                                    <span wire:ignore data-cs-display>Mobile Money</span>
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
                                        <span wire:ignore data-cs-display>MTN Mobile Money</span>
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
                                Send {{ formatCurrency($selectedPrice, 0) }} to <strong>0772000000</strong> (MTN) or <strong>0772000001</strong> (Airtel). Enter the transaction ID below.
                            </div>
                        @elseif ($paymentMethod === 'bank_transfer')
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-300">
                                Transfer {{ formatCurrency($selectedPrice, 0) }} to:<br>
                                <strong>Bank:</strong> Centenary Bank<br>
                                <strong>Account Name:</strong> Akatabo Systems Ltd<br>
                                <strong>Account Number:</strong> 1234567890<br>
                                Enter the reference number below after payment.
                            </div>
                        @else
                            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-xs text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800/30 dark:text-neutral-400">
                                Pay {{ formatCurrency($selectedPrice, 0) }} in person or arrange pickup. Enter a reference if available.
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
