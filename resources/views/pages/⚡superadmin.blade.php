<?php

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Invoice;
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
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Superadmin') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Platform-wide management and oversight') }}</flux:subheading>

    <!-- TABS -->
    <div class="mb-6 flex gap-1 border-b border-neutral-200 dark:border-neutral-700">
        @foreach (['overview' => __('Overview'), 'subscriptions' => __('Subscriptions'), 'users' => __('Users'), 'sales' => __('Sales'), 'logs' => __('Activity Logs')] as $tab => $label)
            <button wire:click="switchTab('{{ $tab }}')" class="px-4 py-2 text-sm font-medium transition-colors {{ $activeTab === $tab ? 'border-b-2 border-accent text-accent' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- ===== OVERVIEW ===== -->
    @if ($activeTab === 'overview')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="p-4">
                <flux:text class="text-sm text-neutral-500">{{ __('Total Businesses') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ number_format($this->total_businesses) }}</flux:heading>
            </flux:card>
            <flux:card class="p-4">
                <flux:text class="text-sm text-neutral-500">{{ __('Total Users') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ number_format($this->total_users) }}</flux:heading>
            </flux:card>
            <flux:card class="p-4">
                <flux:text class="text-sm text-neutral-500">{{ __('Active Subscriptions') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ number_format($this->active_subscriptions_count) }}</flux:heading>
            </flux:card>
            <flux:card class="p-4">
                <flux:text class="text-sm text-neutral-500">{{ __('Total Revenue') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ formatCurrency($this->total_revenue) }}</flux:heading>
            </flux:card>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <flux:card class="p-4">
                <flux:heading size="lg" class="mb-3">{{ __('Revenue (Last 12 Months)') }}</flux:heading>
                @php $months = $this->revenue_by_month; @endphp
                @if (count($months))
                    <div class="space-y-2">
                        @foreach ($months as $month => $total)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ $month }}</span>
                                <span>{{ formatCurrency($total) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:text class="text-neutral-500">{{ __('No revenue data yet.') }}</flux:text>
                @endif
            </flux:card>
            <flux:card class="p-4">
                <flux:heading size="lg" class="mb-3">{{ __('Quick Stats') }}</flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-neutral-500">{{ __('Total Invoices') }}</span><span class="font-medium">{{ number_format($this->total_invoices) }}</span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">{{ __('Total Revenue') }}</span><span class="font-medium">{{ formatCurrency($this->total_revenue) }}</span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">{{ __('Total Businesses') }}</span><span class="font-medium">{{ number_format($this->total_businesses) }}</span></div>
                    <div class="flex justify-between"><span class="text-neutral-500">{{ __('Total Users') }}</span><span class="font-medium">{{ number_format($this->total_users) }}</span></div>
                </div>
            </flux:card>
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

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Business') }}</flux:table.column>
                <flux:table.column>{{ __('Plan') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Amount') }}</flux:table.column>
                <flux:table.column>{{ __('Cycle') }}</flux:table.column>
                <flux:table.column>{{ __('Starts') }}</flux:table.column>
                <flux:table.column>{{ __('Ends') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->subscriptions as $sub)
                    <flux:table.row>
                        <flux:table.cell>{{ $sub->user?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $sub->business?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $sub->plan?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="pill" size="sm"
                                :color="match($sub->status) { 'active' => 'green', 'pending' => 'amber', 'cancelled' => 'red', 'expired' => 'neutral', default => 'neutral' }"
                                :icon="match($sub->status) { 'active' => 'check-circle', 'pending' => 'clock', 'cancelled' => 'x-circle', 'expired' => 'exclamation-triangle', default => 'clock' }"
                            >
                                {{ __(ucfirst($sub->status)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ formatCurrency($sub->amount) }}</flux:table.cell>
                        <flux:table.cell>{{ __(ucfirst($sub->billing_cycle)) }}</flux:table.cell>
                        <flux:table.cell>{{ $sub->starts_at?->format('d M Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $sub->ends_at?->format('d M Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                <flux:button size="xs" variant="ghost" wire:click="startEditSubscription({{ $sub->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="xs" variant="danger" wire:click="confirmDeleteSubscription({{ $sub->id }})">{{ __('Delete') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center text-neutral-500">{{ __('No subscriptions found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

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

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Business') }}</flux:table.column>
                <flux:table.column>{{ __('Joined') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="pill" size="sm"
                                :color="match($user->role) { 'superadmin' => 'red', 'admin' => 'indigo', 'employee' => 'lime', default => 'neutral' }"
                                :icon="match($user->role) { 'superadmin' => 'shield-check', 'admin' => 'user-circle', 'employee' => 'user', default => 'user' }"
                            >
                                {{ __(ucfirst($user->role)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $user->business?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $user->created_at->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                <flux:button size="xs" variant="ghost" wire:click="startEditUser({{ $user->id }})">{{ __('Edit') }}</flux:button>
                                @if ($user->id !== auth()->id())
                                    <flux:button size="xs" variant="danger" wire:click="confirmDeleteUser({{ $user->id }})">{{ __('Delete') }}</flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-neutral-500">{{ __('No users found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="relative overflow-hidden border-l-4 border-l-emerald-500 p-4">
                <div class="absolute right-3 top-3 text-emerald-200">
                    <svg class="size-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <flux:text class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Total Subscription Revenue') }}</flux:text>
                <flux:heading size="xl" class="mt-1 text-emerald-700 dark:text-emerald-300">{{ formatCurrency($stats['total_revenue']) }}</flux:heading>
            </flux:card>
            <flux:card class="relative overflow-hidden border-l-4 border-l-indigo-500 p-4">
                <div class="absolute right-3 top-3 text-indigo-200">
                    <svg class="size-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <flux:text class="text-xs font-medium uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Active Subscriptions') }}</flux:text>
                <flux:heading size="xl" class="mt-1 text-indigo-700 dark:text-indigo-300">{{ number_format($stats['active_subscriptions']) }}</flux:heading>
            </flux:card>
            <flux:card class="relative overflow-hidden border-l-4 border-l-amber-500 p-4">
                <div class="absolute right-3 top-3 text-amber-200">
                    <svg class="size-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <flux:text class="text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('Revenue Today') }}</flux:text>
                <flux:heading size="xl" class="mt-1 text-amber-700 dark:text-amber-300">{{ formatCurrency($stats['revenue_today']) }}</flux:heading>
            </flux:card>
            <flux:card class="relative overflow-hidden border-l-4 border-l-violet-500 p-4">
                <div class="absolute right-3 top-3 text-violet-200">
                    <svg class="size-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                </div>
                <flux:text class="text-xs font-medium uppercase tracking-wider text-violet-600 dark:text-violet-400">{{ __('Revenue This Month') }}</flux:text>
                <flux:heading size="xl" class="mt-1 text-violet-700 dark:text-violet-300">{{ formatCurrency($stats['revenue_month']) }}</flux:heading>
            </flux:card>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <flux:card class="p-4">
                <div class="mb-3 flex items-center gap-2">
                    <svg class="size-5 text-sky-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    <flux:heading size="lg">{{ __('Subscription Summary') }}</flux:heading>
                </div>
                <div class="space-y-2.5 text-sm">
                    <div class="flex items-center justify-between rounded-lg bg-sky-50 px-3 py-2 dark:bg-sky-900/20">
                        <div class="flex items-center gap-2"><svg class="size-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span class="text-neutral-600 dark:text-neutral-400">{{ __('Total Subscriptions') }}</span></div>
                        <span class="font-semibold text-sky-700 dark:text-sky-300">{{ number_format($stats['total_subscriptions']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 dark:bg-emerald-900/20">
                        <div class="flex items-center gap-2"><svg class="size-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span class="text-neutral-600 dark:text-neutral-400">{{ __('Active') }}</span></div>
                        <span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($stats['active_subscriptions']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-amber-50 px-3 py-2 dark:bg-amber-900/20">
                        <div class="flex items-center gap-2"><svg class="size-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><span class="text-neutral-600 dark:text-neutral-400">{{ __('MRR (Monthly)') }}</span></div>
                        <span class="font-semibold text-amber-700 dark:text-amber-300">{{ formatCurrency($stats['monthly_recurring']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-violet-50 px-3 py-2 dark:bg-violet-900/20">
                        <div class="flex items-center gap-2"><svg class="size-4 text-violet-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span class="text-neutral-600 dark:text-neutral-400">{{ __('ARR (Yearly)') }}</span></div>
                        <span class="font-semibold text-violet-700 dark:text-violet-300">{{ formatCurrency($stats['yearly_recurring']) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-neutral-50 px-3 py-2 dark:bg-neutral-800">
                        <div class="flex items-center gap-2"><svg class="size-4 text-neutral-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg><span class="text-neutral-600 dark:text-neutral-400">{{ __('Total Businesses') }}</span></div>
                        <span class="font-semibold text-neutral-700 dark:text-neutral-300">{{ number_format($stats['total_businesses']) }}</span>
                    </div>
                </div>
            </flux:card>
            <flux:card class="p-4">
                <div class="mb-3 flex items-center gap-2">
                    <svg class="size-5 text-rose-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    <flux:heading size="lg">{{ __('Top Businesses by Revenue') }}</flux:heading>
                </div>
                <div class="space-y-1.5 text-sm">
                    @forelse ($this->top_businesses as $i => $biz)
                        <div class="flex items-center justify-between rounded-lg px-3 py-2 {{ $i % 2 === 0 ? 'bg-rose-50 dark:bg-rose-900/15' : 'bg-white dark:bg-transparent' }}">
                            <div class="flex items-center gap-2.5 truncate max-w-[200px]">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $i < 3 ? 'text-rose-600 bg-rose-100 dark:text-rose-300 dark:bg-rose-900/30' : 'text-neutral-500 bg-neutral-100 dark:text-neutral-400 dark:bg-neutral-800' }}">{{ $i + 1 }}</span>
                                <span class="font-medium truncate">{{ $biz->name }}</span>
                            </div>
                            <span class="font-semibold text-rose-700 dark:text-rose-300">{{ formatCurrency($biz->total_revenue ?? 0) }}</span>
                        </div>
                    @empty
                        <flux:text class="text-neutral-500">{{ __('No data yet.') }}</flux:text>
                    @endforelse
                </div>
            </flux:card>
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

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Time') }}</flux:table.column>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Business') }}</flux:table.column>
                <flux:table.column>{{ __('Action') }}</flux:table.column>
                <flux:table.column>{{ __('Description') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->logs as $log)
                    <flux:table.row>
                        <flux:table.cell class="whitespace-nowrap text-xs">{{ $log->created_at->format('d M Y H:i') }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $log->user?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $log->business?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
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
                        </flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate text-xs text-neutral-500">{{ $log->description ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-neutral-500">{{ __('No activity logs yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $this->logs->links(data: ['pageName' => 'logPage']) }}
        </div>
    @endif
</section>
