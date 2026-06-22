<?php

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\PageVisit;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Superadmin')] class extends Component {
    use WithPagination;

    public string $activeTab = 'overview';

    // Subscription filters
    public string $subSearch = '';
    public string $subStatus = '';

    // User filters
    public string $userSearch = '';
    public string $userRole = '';

    // Log filters
    public string $logSearch = '';
    public string $logAction = '';

    // Analytics filters
    public string $analyticsPeriod = '7d';

    // Subscription management
    public bool $showEditSubscriptionModal = false;
    public ?int $editingSubscriptionId = null;
    public string $editPlanId = '';
    public string $editStatus = '';
    public ?string $editNotes = null;

    // User management
    public ?int $editingUserId = null;
    public string $editUserName = '';
    public string $editUserEmail = '';
    public string $editUserRole = '';
    public ?int $editUserBusinessId = null;

    public bool $showDeleteSubscriptionModal = false;
    public bool $showEditUserModal = false;
    public bool $showDeleteUserModal = false;
    public ?int $deletingSubscriptionId = null;
    public ?int $deletingUserId = null;

    public function mount(): void
    {
        if (! auth()->user()->isSuperadmin()) {
            abort(403);
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ========== OVERVIEW STATS ==========

    public function getTotalBusinessesProperty(): int
    {
        return Business::count();
    }

    public function getTotalUsersProperty(): int
    {
        return User::count();
    }

    public function getTotalRevenueProperty(): float
    {
        return Payment::sum('amount');
    }

    public function getTotalInvoicesProperty(): int
    {
        return Invoice::count();
    }

    public function getActiveSubscriptionsCountProperty(): int
    {
        return Subscription::where('status', 'active')->count();
    }

    public function getRevenueByMonthProperty(): array
    {
        return Payment::select(
            DB::raw("DATE_FORMAT(payment_date, '%Y-%m') as month"),
            DB::raw('SUM(amount) as total')
        )
            ->where('payment_date', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
    }

    // ========== SUBSCRIPTIONS ==========

    public function getSubscriptionsProperty()
    {
        return Subscription::query()
            ->when($this->subSearch, fn ($q) => $q->where(function ($q) {
                $q->whereHas('business', fn ($q) => $q->where('name', 'like', "%{$this->subSearch}%"))
                  ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->subSearch}%"))
                  ->orWhere('payment_reference', 'like', "%{$this->subSearch}%");
            }))
            ->when($this->subStatus, fn ($q) => $q->where('status', $this->subStatus))
            ->with(['user', 'business', 'plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, pageName: 'subPage');
    }

    public function getPlansProperty()
    {
        return Plan::orderBy('sort_order')->get();
    }

    public function startEditSubscription(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        $this->editingSubscriptionId = $id;
        $this->editPlanId = (string) $sub->plan_id;
        $this->editStatus = $sub->status;
        $this->editNotes = $sub->notes;
        $this->showEditSubscriptionModal = true;
    }

    public function saveSubscription(): void
    {
        $this->validate([
            'editPlanId' => ['required', 'exists:plans,id'],
            'editStatus' => ['required', 'in:active,pending,cancelled,expired'],
            'editNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $sub = Subscription::findOrFail($this->editingSubscriptionId);
        $sub->update([
            'plan_id' => $this->editPlanId,
            'status' => $this->editStatus,
            'notes' => $this->editNotes,
        ]);

        Flux::toast(variant: 'success', text: __('Subscription updated.'));
        $this->showEditSubscriptionModal = false;
        $this->editingSubscriptionId = null;
    }

    public function cancelEditSubscription(): void
    {
        $this->showEditSubscriptionModal = false;
        $this->editingSubscriptionId = null;
    }

    public function confirmDeleteSubscription(int $id): void
    {
        $this->deletingSubscriptionId = $id;
        $this->showDeleteSubscriptionModal = true;
    }

    public function deleteSubscription(): void
    {
        $sub = Subscription::findOrFail($this->deletingSubscriptionId);
        $sub->delete();
        Flux::toast(variant: 'success', text: __('Subscription deleted.'));
        $this->showDeleteSubscriptionModal = false;
        $this->deletingSubscriptionId = null;
    }

    public function cancelDeleteSubscription(): void
    {
        $this->showDeleteSubscriptionModal = false;
        $this->deletingSubscriptionId = null;
    }

    // ========== USERS ==========

    public function getUsersProperty()
    {
        return User::query()
            ->when($this->userSearch, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->userSearch}%")
                    ->orWhere('email', 'like', "%{$this->userSearch}%");
            }))
            ->when($this->userRole, fn ($q) => $q->where('role', $this->userRole))
            ->with('business')
            ->orderBy('created_at', 'desc')
            ->paginate(10, pageName: 'userPage');
    }

    public function startEditUser(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingUserId = $id;
        $this->editUserName = $user->name;
        $this->editUserEmail = $user->email;
        $this->editUserRole = $user->role;
        $this->editUserBusinessId = $user->business_id;
        $this->showEditUserModal = true;
    }

    public function saveUser(): void
    {
        $this->validate([
            'editUserName' => ['required', 'string', 'max:255'],
            'editUserEmail' => ['required', 'email', 'max:255'],
            'editUserRole' => ['required', 'in:superadmin,admin,employee'],
            'editUserBusinessId' => ['nullable', 'exists:businesses,id'],
        ]);

        $user = User::findOrFail($this->editingUserId);
        $user->update([
            'name' => $this->editUserName,
            'email' => $this->editUserEmail,
            'role' => $this->editUserRole,
            'business_id' => $this->editUserBusinessId ?: null,
        ]);

        Flux::toast(variant: 'success', text: __('User updated.'));
        $this->showEditUserModal = false;
        $this->editingUserId = null;
    }

    public function cancelEditUser(): void
    {
        $this->showEditUserModal = false;
        $this->editingUserId = null;
    }

    public function confirmDeleteUser(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast(variant: 'danger', text: __('You cannot delete yourself.'));
            return;
        }
        $this->deletingUserId = $id;
        $this->showDeleteUserModal = true;
    }

    public function deleteUser(): void
    {
        $user = User::findOrFail($this->deletingUserId);
        if ($user->id === auth()->id()) {
            return;
        }
        $user->delete();
        Flux::toast(variant: 'success', text: __('User deleted.'));
        $this->showDeleteUserModal = false;
        $this->deletingUserId = null;
    }

    public function cancelDeleteUser(): void
    {
        $this->showDeleteUserModal = false;
        $this->deletingUserId = null;
    }

    // ========== SALES REPORT ==========

    public function getSalesStatsProperty(): array
    {
        return [
            'total_revenue' => Subscription::sum('amount'),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_subscriptions' => Subscription::count(),
            'total_businesses' => Business::count(),
            'revenue_today' => Subscription::whereDate('paid_at', today())->sum('amount'),
            'revenue_month' => Subscription::whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount'),
            'monthly_recurring' => Subscription::where('status', 'active')->where('billing_cycle', 'monthly')->sum('amount'),
            'yearly_recurring' => Subscription::where('status', 'active')->where('billing_cycle', 'yearly')->sum('amount'),
        ];
    }

    public function getTopBusinessesProperty()
    {
        return Business::select('businesses.*')
            ->selectSub(function ($q) {
                $q->from('subscriptions')
                    ->whereColumn('subscriptions.business_id', 'businesses.id')
                    ->selectRaw('COALESCE(SUM(amount), 0)');
            }, 'total_revenue')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    }

    // ========== ACTIVITY LOGS ==========

    public function getLogsProperty()
    {
        return ActivityLog::query()
            ->when($this->logSearch, fn ($q) => $q->where(function ($q) {
                $q->where('description', 'like', "%{$this->logSearch}%")
                    ->orWhere('action', 'like', "%{$this->logSearch}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->logSearch}%"))
                    ->orWhereHas('business', fn ($q) => $q->where('name', 'like', "%{$this->logSearch}%"));
            }))
            ->when($this->logAction, fn ($q) => $q->where('action', $this->logAction))
            ->with(['user', 'business'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, pageName: 'logPage');
    }

    public function getLogActionsProperty(): array
    {
        return ActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->toArray();
    }

    public function getBusinessesProperty()
    {
        return Business::orderBy('name')->get(['id', 'name']);
    }

    // ========== ANALYTICS ==========

    private function analyticsDateRange(): array
    {
        return match ($this->analyticsPeriod) {
            '24h' => [now()->subDay(), now()],
            '7d' => [now()->subDays(7), now()],
            '30d' => [now()->subDays(30), now()],
            '90d' => [now()->subDays(90), now()],
            default => [now()->subDays(7), now()],
        };
    }

    public function getAnalyticsStatsProperty(): array
    {
        [$from, $to] = $this->analyticsDateRange();

        $totalVisits = PageVisit::whereBetween('visited_at', [$from, $to])->count();
        $uniqueVisitors = PageVisit::whereBetween('visited_at', [$from, $to])->distinct('ip_address')->count('ip_address');
        $visitsToday = PageVisit::whereDate('visited_at', today())->count();
        $uniqueToday = PageVisit::whereDate('visited_at', today())->distinct('ip_address')->count('ip_address');

        return [
            'total_visits' => $totalVisits,
            'unique_visitors' => $uniqueVisitors,
            'visits_today' => $visitsToday,
            'unique_today' => $uniqueToday,
        ];
    }

    public function getVisitsOverTimeProperty(): array
    {
        [$from, $to] = $this->analyticsDateRange();

        return PageVisit::select(
            DB::raw('DATE(visited_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(DISTINCT ip_address) as unique_count')
        )
            ->whereBetween('visited_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getTopPagesProperty()
    {
        [$from, $to] = $this->analyticsDateRange();

        return PageVisit::select('visited_url',
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(DISTINCT ip_address) as unique_count')
        )
            ->whereBetween('visited_at', [$from, $to])
            ->groupBy('visited_url')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    }

    public function getTopCountriesProperty()
    {
        [$from, $to] = $this->analyticsDateRange();

        return PageVisit::select('country',
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(DISTINCT ip_address) as unique_count')
        )
            ->whereBetween('visited_at', [$from, $to])
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    }

    public function getRecentVisitsProperty()
    {
        [$from, $to] = $this->analyticsDateRange();

        return PageVisit::whereBetween('visited_at', [$from, $to])
            ->with('business')
            ->orderBy('visited_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function getTotalVisitsAllTimeProperty(): int
    {
        return PageVisit::count();
    }

    public function getUniqueVisitorsAllTimeProperty(): int
    {
        return PageVisit::distinct('ip_address')->count('ip_address');
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Superadmin') }}</flux:heading>
            <flux:subheading>{{ __('Platform-wide management and oversight') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2 text-xs text-neutral-400">
            <flux:icon name="calendar-days" class="size-3.5" />
            <span>{{ now()->format('l, d F Y') }}</span>
        </div>
    </div>

    <!-- TABS -->
    <div class="mb-6 flex gap-1 border-b border-neutral-200 dark:border-neutral-700">
        @foreach (['overview' => __('Overview'), 'analytics' => __('Analytics'), 'subscriptions' => __('Subscriptions'), 'users' => __('Users'), 'sales' => __('Sales'), 'logs' => __('Activity Logs')] as $tab => $label)
            <button wire:click="switchTab('{{ $tab }}')" class="relative px-4 py-2.5 text-sm font-medium transition-colors {{ $activeTab === $tab ? 'text-accent' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                {{ $label }}
                @if ($activeTab === $tab)
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-accent"></span>
                @endif
            </button>
        @endforeach
    </div>

    <!-- ===== OVERVIEW ===== -->
    @if ($activeTab === 'overview')
        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm dark:border-emerald-800/30 dark:from-emerald-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-emerald-100/60 dark:bg-emerald-900/30">
                    <flux:icon name="building-storefront" variant="solid" class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Total Businesses') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ number_format($this->total_businesses) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('registered') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm dark:border-blue-800/30 dark:from-blue-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-blue-100/60 dark:bg-blue-900/30">
                    <flux:icon name="users" variant="solid" class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">{{ __('Total Users') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ number_format($this->total_users) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ __('platform wide') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm dark:border-amber-800/30 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-amber-100/60 dark:bg-amber-900/30">
                    <flux:icon name="check-badge" variant="solid" class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('Active Subscriptions') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ number_format($this->active_subscriptions_count) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ number_format($this->total_users > 0 ? round(($this->active_subscriptions_count / max($this->total_users, 1)) * 100) : 0) }}% {{ __('adoption') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm dark:border-violet-800/30 dark:from-violet-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-violet-100/60 dark:bg-violet-900/30">
                    <flux:icon name="banknotes" variant="solid" class="size-6 text-violet-600 dark:text-violet-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">{{ __('Total Revenue') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($this->total_revenue) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-violet-100 px-2 py-0.5 font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">{{ number_format($this->total_invoices) }} {{ __('invoices') }}</span>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900/30">
                            <flux:icon name="arrow-trending-up" class="size-4 text-sky-600 dark:text-sky-400" />
                        </div>
                        <flux:heading size="sm">{{ __('Revenue (Last 12 Months)') }}</flux:heading>
                    </div>
                </div>
                <div class="p-5">
                    @php $months = $this->revenue_by_month; @endphp
                    @if (count($months))
                        @php $maxRev = max($months); @endphp
                        <div class="space-y-3">
                            @foreach ($months as $month => $total)
                                @php $pct = $maxRev > 0 ? ($total / $maxRev) * 100 : 0; @endphp
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $month }}</span>
                                        <span class="font-semibold text-neutral-900 dark:text-white">{{ formatCurrency($total) }}</span>
                                    </div>
                                    <div class="relative h-2.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center py-8">
                            <flux:icon name="chart-bar" class="size-8 text-neutral-300 dark:text-neutral-600" />
                            <p class="mt-2 text-sm text-neutral-400">{{ __('No revenue data yet.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-900/30">
                            <flux:icon name="chart-pie" class="size-4 text-rose-600 dark:text-rose-400" />
                        </div>
                        <flux:heading size="sm">{{ __('Platform Snapshot') }}</flux:heading>
                    </div>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-sky-50 px-4 py-3 dark:bg-sky-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="document-text" variant="solid" class="size-4 text-sky-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Total Invoices') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-sky-700 dark:text-sky-300">{{ number_format($this->total_invoices) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3 dark:bg-emerald-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="banknotes" variant="solid" class="size-4 text-emerald-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Total Revenue') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ formatCurrency($this->total_revenue) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-indigo-50 px-4 py-3 dark:bg-indigo-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="building-storefront" variant="solid" class="size-4 text-indigo-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Total Businesses') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">{{ number_format($this->total_businesses) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-amber-50 px-4 py-3 dark:bg-amber-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="users" variant="solid" class="size-4 text-amber-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Total Users') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ number_format($this->total_users) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-violet-50 px-4 py-3 dark:bg-violet-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="check-badge" variant="solid" class="size-4 text-violet-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Active Subscriptions') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-violet-700 dark:text-violet-300">{{ number_format($this->active_subscriptions_count) }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ===== ANALYTICS ===== -->
    @if ($activeTab === 'analytics')
        @php $aStats = $this->analytics_stats; @endphp
        <div class="mb-4 flex items-center gap-4">
            <flux:select wire:model.live="analyticsPeriod" class="max-w-[180px]">
                <option value="24h">{{ __('Last 24 Hours') }}</option>
                <option value="7d">{{ __('Last 7 Days') }}</option>
                <option value="30d">{{ __('Last 30 Days') }}</option>
                <option value="90d">{{ __('Last 90 Days') }}</option>
            </flux:select>
            <div class="flex items-center gap-2 text-xs text-neutral-400">
                <flux:icon name="globe-alt" class="size-3.5" />
                <span>{{ __('All Time') }}: {{ number_format($this->total_visits_all_time) }} {{ __('visits') }} · {{ number_format($this->unique_visitors_all_time) }} {{ __('unique') }}</span>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative overflow-hidden rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm dark:border-sky-800/30 dark:from-sky-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-sky-100/60 dark:bg-sky-900/30">
                    <flux:icon name="eye" variant="solid" class="size-6 text-sky-600 dark:text-sky-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-600 dark:text-sky-400">{{ __('Page Views') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ number_format($aStats['total_visits']) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-sky-100 px-2 py-0.5 font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ number_format($aStats['visits_today']) }} {{ __('today') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm dark:border-violet-800/30 dark:from-violet-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-violet-100/60 dark:bg-violet-900/30">
                    <flux:icon name="users" variant="solid" class="size-6 text-violet-600 dark:text-violet-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">{{ __('Unique Visitors') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ number_format($aStats['unique_visitors']) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-violet-100 px-2 py-0.5 font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">{{ number_format($aStats['unique_today']) }} {{ __('today') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm dark:border-emerald-800/30 dark:from-emerald-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-emerald-100/60 dark:bg-emerald-900/30">
                    <flux:icon name="globe-alt" variant="solid" class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Countries') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ number_format($this->top_countries->count()) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('detected') }}</span>
                </div>
            </div>

            @php
                $avgVisitsPerDay = $aStats['total_visits'] > 0 && $aStats['unique_visitors'] > 0 ? round($aStats['total_visits'] / $aStats['unique_visitors'], 1) : 0;
            @endphp
            <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm dark:border-amber-800/30 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-amber-100/60 dark:bg-amber-900/30">
                    <flux:icon name="chart-bar" variant="solid" class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('Avg Views/Visitor') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ $avgVisitsPerDay }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ __('pages per visit') }}</span>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Visits Over Time --}}
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900/30">
                            <flux:icon name="chart-bar" class="size-4 text-sky-600 dark:text-sky-400" />
                        </div>
                        <flux:heading size="sm">{{ __('Visits Over Time') }}</flux:heading>
                    </div>
                </div>
                <div class="p-5">
                    @php $visitsOverTime = $this->visits_over_time; @endphp
                    @if (count($visitsOverTime))
                        @php $maxVisits = max(array_column($visitsOverTime, 'count')); @endphp
                        <div class="space-y-2">
                            @foreach ($visitsOverTime as $day)
                                @php $pct = $maxVisits > 0 ? ($day['count'] / $maxVisits) * 100 : 0; @endphp
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ \Carbon\Carbon::parse($day['date'])->format('d M') }}</span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-neutral-400">{{ $day['unique_count'] }} {{ __('unique') }}</span>
                                            <span class="font-semibold text-neutral-900 dark:text-white">{{ $day['count'] }}</span>
                                        </div>
                                    </div>
                                    <div class="relative h-2.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-600 transition-all duration-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center py-8">
                            <flux:icon name="chart-bar" class="size-8 text-neutral-300 dark:text-neutral-600" />
                            <p class="mt-2 text-sm text-neutral-400">{{ __('No visit data yet.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Top Pages --}}
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                            <flux:icon name="document-text" class="size-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <flux:heading size="sm">{{ __('Top Pages') }}</flux:heading>
                    </div>
                    <span class="text-xs font-medium text-neutral-400">{{ __('Top 10') }}</span>
                </div>
                <div class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                    @forelse ($this->top_pages as $i => $page)
                        <div class="flex items-center gap-4 px-5 py-3 transition hover:bg-neutral-50 dark:hover:bg-white/5">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xs font-bold text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-neutral-900 dark:text-white" title="{{ $page->visited_url }}">{{ $page->visited_url }}</p>
                                <p class="text-xs text-neutral-400">{{ $page->unique_count }} {{ __('unique') }} · {{ $page->count }} {{ __('views') }}</p>
                            </div>
                            @php $pagePct = $aStats['total_visits'] > 0 ? round(($page->count / $aStats['total_visits']) * 100) : 0; @endphp
                            <span class="shrink-0 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ $pagePct }}%</span>
                        </div>
                    @empty
                        <div class="flex flex-col items-center py-8">
                            <flux:icon name="document-text" class="size-8 text-neutral-300 dark:text-neutral-600" />
                            <p class="mt-2 text-sm text-neutral-400">{{ __('No page data yet.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Top Countries --}}
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                            <flux:icon name="globe-alt" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <flux:heading size="sm">{{ __('Top Countries') }}</flux:heading>
                    </div>
                    <span class="text-xs font-medium text-neutral-400">{{ __('Top 10') }}</span>
                </div>
                <div class="p-5">
                    @php $countries = $this->top_countries; @endphp
                    @if ($countries->isNotEmpty())
                        @php $maxCountry = $countries->max('count'); @endphp
                        <div class="space-y-3">
                            @foreach ($countries as $c)
                                @php $cPct = $maxCountry > 0 ? round(($c->count / $maxCountry) * 100) : 0; @endphp
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-neutral-900 dark:text-white">{{ $c->country ?? __('Unknown') }}</span>
                                            <span class="text-xs text-neutral-400">{{ $c->unique_count }} {{ __('unique') }}</span>
                                        </div>
                                        <span class="font-semibold text-neutral-900 dark:text-white">{{ $c->count }} {{ __('visits') }}</span>
                                    </div>
                                    <div class="relative h-2 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-teal-500 transition-all duration-500" style="width: {{ $cPct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center py-8">
                            <flux:icon name="globe-alt" class="size-8 text-neutral-300 dark:text-neutral-600" />
                            <p class="mt-2 text-sm text-neutral-400">{{ __('No country data yet.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Visits --}}
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                            <flux:icon name="clock" class="size-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <flux:heading size="sm">{{ __('Recent Visits') }}</flux:heading>
                    </div>
                    <span class="text-xs font-medium text-neutral-400">{{ __('Last 50') }}</span>
                </div>
                <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b border-neutral-100 bg-neutral-50/80 text-left dark:border-neutral-700/50 dark:bg-neutral-800/30">
                                <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Time') }}</th>
                                <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('IP') }}</th>
                                <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Country') }}</th>
                                <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Page') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                            @forelse ($this->recent_visits as $visit)
                                <tr class="transition hover:bg-neutral-50 dark:hover:bg-white/5">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-xs text-neutral-500">{{ $visit->visited_at->format('d M H:i') }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ $visit->ip_address }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-xs">
                                        @if ($visit->country)
                                            <flux:badge variant="pill" size="sm" color="sky">{{ $visit->country }}</flux:badge>
                                        @else
                                            <span class="text-neutral-400">—</span>
                                        @endif
                                    </td>
                                    <td class="max-w-[200px] truncate px-4 py-2.5 text-xs text-neutral-700 dark:text-neutral-300" title="{{ $visit->visited_url }}">{{ $visit->visited_url }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="flex flex-col items-center py-8 text-center">
                                            <flux:icon name="clock" class="size-8 text-neutral-300 dark:text-neutral-600" />
                                            <p class="mt-2 text-sm text-neutral-400">{{ __('No visits recorded yet.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ===== SUBSCRIPTIONS ===== -->
    @if ($activeTab === 'subscriptions')
        <div class="mb-4 flex items-center gap-4">
            <flux:input wire:model.live.debounce="subSearch" placeholder="{{ __('Search by business...') }}" class="max-w-xs" />
            <flux:select wire:model.live="subStatus" class="max-w-[180px]">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
            </flux:select>
        </div>

        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/80 text-left dark:border-neutral-700/50 dark:bg-neutral-800/30">
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('User') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Business') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Plan') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Status') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Amount') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Cycle') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Starts') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Ends') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                        @forelse ($this->subscriptions as $sub)
                            <tr class="transition hover:bg-neutral-50 dark:hover:bg-white/5">
                                <td class="px-5 py-3.5 text-neutral-900 dark:text-white">{{ $sub->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5 font-medium text-neutral-900 dark:text-white">{{ $sub->business?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-neutral-700 dark:text-neutral-300">{{ $sub->plan?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    <flux:badge variant="pill" size="sm"
                                        :color="match($sub->status) { 'active' => 'green', 'pending' => 'amber', 'cancelled' => 'red', 'expired' => 'neutral', default => 'neutral' }"
                                        :icon="match($sub->status) { 'active' => 'check-circle', 'pending' => 'clock', 'cancelled' => 'x-circle', 'expired' => 'exclamation-triangle', default => 'clock' }"
                                    >
                                        {{ __(ucfirst($sub->status)) }}
                                    </flux:badge>
                                </td>
                                <td class="px-5 py-3.5 font-medium text-neutral-900 dark:text-white">{{ formatCurrency($sub->amount) }}</td>
                                <td class="px-5 py-3.5 text-neutral-700 dark:text-neutral-300">{{ __(ucfirst($sub->billing_cycle)) }}</td>
                                <td class="px-5 py-3.5 text-neutral-500">{{ $sub->starts_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-neutral-500">{{ $sub->ends_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex gap-1">
                                        <flux:button size="xs" variant="ghost" wire:click="startEditSubscription({{ $sub->id }})">{{ __('Edit') }}</flux:button>
                                        <flux:button size="xs" variant="danger" wire:click="confirmDeleteSubscription({{ $sub->id }})">{{ __('Delete') }}</flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-10 text-center text-neutral-500">{{ __('No subscriptions found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $this->subscriptions->links(data: ['pageName' => 'subPage']) }}
        </div>

        <flux:modal wire:model="showEditSubscriptionModal" class="w-full max-w-lg">
            @if ($editingSubscriptionId)
                <flux:heading size="lg" class="mb-4">{{ __('Edit Subscription') }}</flux:heading>
                <div class="space-y-4">
                    <flux:select wire:model="editPlanId" :label="__('Plan')">
                        @foreach ($this->plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — {{ formatCurrency($plan->price_monthly) }}/mo</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="editStatus" :label="__('Status')">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                        <option value="expired">{{ __('Expired') }}</option>
                    </flux:select>
                    <flux:field>
                        <flux:label>{{ __('Notes') }}</flux:label>
                        <flux:textarea wire:model="editNotes" rows="3" />
                    </flux:field>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="cancelEditSubscription">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" wire:click="saveSubscription">{{ __('Save') }}</flux:button>
                </div>
            @endif
        </flux:modal>

        <flux:modal wire:model="showDeleteSubscriptionModal" class="w-full max-w-md">
            @if ($deletingSubscriptionId)
                <flux:heading size="lg" class="mb-2">{{ __('Delete Subscription') }}</flux:heading>
                <flux:text class="mb-6">{{ __('Are you sure you want to delete this subscription? This action cannot be undone.') }}</flux:text>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="cancelDeleteSubscription">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="danger" wire:click="deleteSubscription">{{ __('Delete') }}</flux:button>
                </div>
            @endif
        </flux:modal>
    @endif

    <!-- ===== USERS ===== -->
    @if ($activeTab === 'users')
        <div class="mb-4 flex items-center gap-4">
            <flux:input wire:model.live.debounce="userSearch" placeholder="{{ __('Search by name or email...') }}" class="max-w-xs" />
            <flux:select wire:model.live="userRole" class="max-w-[180px]">
                <option value="">{{ __('All roles') }}</option>
                <option value="superadmin">{{ __('Superadmin') }}</option>
                <option value="admin">{{ __('Admin') }}</option>
                <option value="employee">{{ __('Employee') }}</option>
            </flux:select>
        </div>

        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/80 text-left dark:border-neutral-700/50 dark:bg-neutral-800/30">
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Name') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Email') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Role') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Business') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Joined') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                        @forelse ($this->users as $user)
                            <tr class="transition hover:bg-neutral-50 dark:hover:bg-white/5">
                                <td class="px-5 py-3.5 font-medium text-neutral-900 dark:text-white">{{ $user->name }}</td>
                                <td class="px-5 py-3.5 text-neutral-700 dark:text-neutral-300">{{ $user->email }}</td>
                                <td class="px-5 py-3.5">
                                    <flux:badge variant="pill" size="sm"
                                        :color="match($user->role) { 'superadmin' => 'red', 'admin' => 'indigo', 'employee' => 'lime', default => 'neutral' }"
                                        :icon="match($user->role) { 'superadmin' => 'shield-check', 'admin' => 'user-circle', 'employee' => 'user', default => 'user' }"
                                    >
                                        {{ __(ucfirst($user->role)) }}
                                    </flux:badge>
                                </td>
                                <td class="px-5 py-3.5 text-neutral-700 dark:text-neutral-300">{{ $user->business?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-neutral-500">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex gap-1">
                                        <flux:button size="xs" variant="ghost" wire:click="startEditUser({{ $user->id }})">{{ __('Edit') }}</flux:button>
                                        @if ($user->id !== auth()->id())
                                            <flux:button size="xs" variant="danger" wire:click="confirmDeleteUser({{ $user->id }})">{{ __('Delete') }}</flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-neutral-500">{{ __('No users found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $this->users->links(data: ['pageName' => 'userPage']) }}
        </div>

        <flux:modal wire:model="showEditUserModal" class="w-full max-w-lg">
            @if ($editingUserId)
                <flux:heading size="lg" class="mb-4">{{ __('Edit User') }}</flux:heading>
                <div class="space-y-4">
                    <flux:input wire:model="editUserName" :label="__('Name')" />
                    <flux:input wire:model="editUserEmail" :label="__('Email')" type="email" />
                    <flux:select wire:model="editUserRole" :label="__('Role')">
                        <option value="superadmin">{{ __('Superadmin') }}</option>
                        <option value="admin">{{ __('Admin') }}</option>
                        <option value="employee">{{ __('Employee') }}</option>
                    </flux:select>
                    <flux:select wire:model="editUserBusinessId" :label="__('Business')">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach ($this->businesses as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="cancelEditUser">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" wire:click="saveUser">{{ __('Save') }}</flux:button>
                </div>
            @endif
        </flux:modal>

        <flux:modal wire:model="showDeleteUserModal" class="w-full max-w-md">
            @if ($deletingUserId)
                <flux:heading size="lg" class="mb-2">{{ __('Delete User') }}</flux:heading>
                <flux:text class="mb-6">{{ __('Are you sure you want to delete this user? This action cannot be undone.') }}</flux:text>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="cancelDeleteUser">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="danger" wire:click="deleteUser">{{ __('Delete') }}</flux:button>
                </div>
            @endif
        </flux:modal>
    @endif

    <!-- ===== SALES ===== -->
    @if ($activeTab === 'sales')
        @php $stats = $this->sales_stats; @endphp
        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm dark:border-emerald-800/30 dark:from-emerald-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-emerald-100/60 dark:bg-emerald-900/30">
                    <flux:icon name="banknotes" variant="solid" class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Total Subscription Revenue') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($stats['total_revenue']) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $stats['total_subscriptions'] }} {{ __('subscriptions') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-5 shadow-sm dark:border-indigo-800/30 dark:from-indigo-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-indigo-100/60 dark:bg-indigo-900/30">
                    <flux:icon name="users" variant="solid" class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Active Subscriptions') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ number_format($stats['active_subscriptions']) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $stats['total_subscriptions'] > 0 ? round(($stats['active_subscriptions'] / $stats['total_subscriptions']) * 100) : 0 }}% {{ __('active rate') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm dark:border-amber-800/30 dark:from-amber-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-amber-100/60 dark:bg-amber-900/30">
                    <flux:icon name="clock" variant="solid" class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('Revenue Today') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($stats['revenue_today']) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ now()->format('d M') }}</span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm dark:border-violet-800/30 dark:from-violet-950/30 dark:to-[oklch(0.21_0.02_320.19)]">
                <div class="absolute right-0 top-0 flex size-14 items-center justify-center rounded-bl-2xl bg-violet-100/60 dark:bg-violet-900/30">
                    <flux:icon name="calendar-days" variant="solid" class="size-6 text-violet-600 dark:text-violet-400" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">{{ __('Revenue This Month') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ formatCurrency($stats['revenue_month']) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-violet-100 px-2 py-0.5 font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">{{ now()->format('F Y') }}</span>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-900/30">
                            <flux:icon name="book-open" class="size-4 text-sky-600 dark:text-sky-400" />
                        </div>
                        <flux:heading size="sm">{{ __('Subscription Summary') }}</flux:heading>
                    </div>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-sky-50 px-4 py-3 dark:bg-sky-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="users" variant="solid" class="size-4 text-sky-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Total Subscriptions') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-sky-700 dark:text-sky-300">{{ number_format($stats['total_subscriptions']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3 dark:bg-emerald-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="check-badge" variant="solid" class="size-4 text-emerald-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Active') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($stats['active_subscriptions']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-amber-50 px-4 py-3 dark:bg-amber-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="banknotes" variant="solid" class="size-4 text-amber-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('MRR (Monthly)') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ formatCurrency($stats['monthly_recurring']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-violet-50 px-4 py-3 dark:bg-violet-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="arrow-trending-up" variant="solid" class="size-4 text-violet-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('ARR (Yearly)') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-violet-700 dark:text-violet-300">{{ formatCurrency($stats['yearly_recurring']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-indigo-50 px-4 py-3 dark:bg-indigo-900/20">
                        <div class="flex items-center gap-2.5">
                            <flux:icon name="building-storefront" variant="solid" class="size-4 text-indigo-500" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('Total Businesses') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">{{ number_format($stats['total_businesses']) }}</span>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4 dark:border-neutral-700/50">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-900/30">
                            <flux:icon name="trophy" class="size-4 text-rose-600 dark:text-rose-400" />
                        </div>
                        <flux:heading size="sm">{{ __('Top Businesses by Revenue') }}</flux:heading>
                    </div>
                    <span class="text-xs font-medium text-neutral-400">{{ __('Top 10') }}</span>
                </div>
                <div class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                    @forelse ($this->top_businesses as $i => $biz)
                        <div class="flex items-center gap-4 px-5 py-3.5 transition hover:bg-neutral-50 dark:hover:bg-white/5">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $i < 3 ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400' }}">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <span class="text-sm font-medium text-neutral-900 dark:text-white truncate block">{{ $biz->name }}</span>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-rose-700 dark:text-rose-300">{{ formatCurrency($biz->total_revenue ?? 0) }}</span>
                        </div>
                    @empty
                        <div class="flex flex-col items-center py-8">
                            <flux:icon name="trophy" class="size-8 text-neutral-300 dark:text-neutral-600" />
                            <p class="mt-2 text-sm text-neutral-400">{{ __('No data yet.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- ===== ACTIVITY LOGS ===== -->
    @if ($activeTab === 'logs')
        <div class="mb-4 flex items-center gap-4">
            <flux:input wire:model.live.debounce="logSearch" placeholder="{{ __('Search logs...') }}" class="max-w-xs" />
            <flux:select wire:model.live="logAction" class="max-w-[200px]">
                <option value="">{{ __('All actions') }}</option>
                @foreach ($this->log_actions as $action)
                    <option value="{{ $action }}">{{ __(ucfirst(str_replace('_', ' ', $action))) }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/80 text-left dark:border-neutral-700/50 dark:bg-neutral-800/30">
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Time') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('User') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Business') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Action') }}</th>
                            <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-700/50">
                        @forelse ($this->logs as $log)
                            <tr class="transition hover:bg-neutral-50 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-5 py-3.5 text-xs text-neutral-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td class="px-5 py-3.5 font-medium text-neutral-900 dark:text-white">{{ $log->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-neutral-700 dark:text-neutral-300">{{ $log->business?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $logColors = [
                                            'invoice_created' => 'blue',
                                            'invoice_updated' => 'neutral',
                                            'invoice_deleted' => 'red',
                                            'quotation_created' => 'blue',
                                            'quotation_updated' => 'neutral',
                                            'quotation_deleted' => 'red',
                                            'payment_recorded' => 'green',
                                            'payment_deleted' => 'red',
                                            'customer_created' => 'sky',
                                            'customer_updated' => 'neutral',
                                            'customer_deleted' => 'red',
                                        ];
                                        $logIcons = [
                                            'invoice_created' => 'document-plus',
                                            'invoice_updated' => 'document',
                                            'invoice_deleted' => 'trash',
                                            'quotation_created' => 'document-plus',
                                            'quotation_updated' => 'document',
                                            'quotation_deleted' => 'trash',
                                            'payment_recorded' => 'banknotes',
                                            'payment_deleted' => 'trash',
                                            'customer_created' => 'user-plus',
                                            'customer_updated' => 'user',
                                            'customer_deleted' => 'trash',
                                        ];
                                    @endphp
                                    <flux:badge variant="pill" size="sm"
                                        :color="$logColors[$log->action] ?? 'neutral'"
                                        :icon="$logIcons[$log->action] ?? 'clock'"
                                    >
                                        {{ str_replace('_', ' ', $log->action) }}
                                    </flux:badge>
                                </td>
                                <td class="max-w-xs truncate px-5 py-3.5 text-xs text-neutral-500">{{ $log->description ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-neutral-500">{{ __('No activity logs yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $this->logs->links(data: ['pageName' => 'logPage']) }}
        </div>
    @endif
</section>
