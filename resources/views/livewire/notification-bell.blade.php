<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notifications')] class extends Component {
    public bool $showPanel = false;

    public function getListeners(): array
    {
        return ['notification-sent' => '$refresh'];
    }

    public function togglePanel(): void
    {
        $this->showPanel = !$this->showPanel;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function clearAll(): void
    {
        auth()->user()->notifications()->delete();
    }

    public function with(): array
    {
        return [
            'unread_count' => auth()->user()->unreadNotifications->count(),
            'notifications' => auth()->user()->notifications()->latest()->take(50)->get(),
        ];
    }
}; ?>

<?php
$colorMap = [
    'blue' => [
        'bg' => 'bg-blue-100 dark:bg-blue-900/30',
        'text' => 'text-blue-600 dark:text-blue-400',
        'border' => 'bg-blue-500',
        'tint' => 'bg-blue-50/50 dark:bg-blue-900/10',
        'hover' => 'hover:bg-blue-50 dark:hover:bg-blue-900/5',
        'dot' => 'bg-blue-500',
        'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    ],
    'emerald' => [
        'bg' => 'bg-emerald-100 dark:bg-emerald-900/30',
        'text' => 'text-emerald-600 dark:text-emerald-400',
        'border' => 'bg-emerald-500',
        'tint' => 'bg-emerald-50/50 dark:bg-emerald-900/10',
        'hover' => 'hover:bg-emerald-50 dark:hover:bg-emerald-900/5',
        'dot' => 'bg-emerald-500',
        'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    ],
    'indigo' => [
        'bg' => 'bg-indigo-100 dark:bg-indigo-900/30',
        'text' => 'text-indigo-600 dark:text-indigo-400',
        'border' => 'bg-indigo-500',
        'tint' => 'bg-indigo-50/50 dark:bg-indigo-900/10',
        'hover' => 'hover:bg-indigo-50 dark:hover:bg-indigo-900/5',
        'dot' => 'bg-indigo-500',
        'badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
    ],
    'amber' => [
        'bg' => 'bg-amber-100 dark:bg-amber-900/30',
        'text' => 'text-amber-600 dark:text-amber-400',
        'border' => 'bg-amber-500',
        'tint' => 'bg-amber-50/50 dark:bg-amber-900/10',
        'hover' => 'hover:bg-amber-50 dark:hover:bg-amber-900/5',
        'dot' => 'bg-amber-500',
        'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    ],
    'teal' => [
        'bg' => 'bg-teal-100 dark:bg-teal-900/30',
        'text' => 'text-teal-600 dark:text-teal-400',
        'border' => 'bg-teal-500',
        'tint' => 'bg-teal-50/50 dark:bg-teal-900/10',
        'hover' => 'hover:bg-teal-50 dark:hover:bg-teal-900/5',
        'dot' => 'bg-teal-500',
        'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
    ],
    'violet' => [
        'bg' => 'bg-violet-100 dark:bg-violet-900/30',
        'text' => 'text-violet-600 dark:text-violet-400',
        'border' => 'bg-violet-500',
        'tint' => 'bg-violet-50/50 dark:bg-violet-900/10',
        'hover' => 'hover:bg-violet-50 dark:hover:bg-violet-900/5',
        'dot' => 'bg-violet-500',
        'badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
    ],
];

$iconMap = [
    'invoice_created' => 'document-text',
    'invoice_paid' => 'banknotes',
    'quotation_created' => 'document-text',
    'quotation_converted' => 'arrow-right-circle',
    'payment_recorded' => 'credit-card',
    'customer_created' => 'user-plus',
];

?>

<div x-data="{ open: false, showClearConfirm: false }" x-on:keydown.escape.window="open = false; $wire.closePanel()" class="relative" wire:poll.60s>
    <button
        x-on:click="open = !open; $wire.togglePanel()"
        class="relative flex size-9 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white"
        title="{{ __('Notifications') }}"
    >
        <flux:icon name="bell" class="size-5" />
        @if ($unread_count > 0)
            <span class="absolute -right-1 -top-1 flex min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white shadow-sm ring-2 ring-white dark:ring-neutral-900">
                {{ $unread_count > 99 ? '99+' : $unread_count }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-on:click="open = false; $wire.closePanel()"
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/30"
        style="display: none"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition-transform duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed right-0 top-0 z-50 flex h-full w-full max-w-md flex-col border-l border-neutral-200 bg-white shadow-2xl dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]"
        style="display: none"
    >
        <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4 dark:border-neutral-700/50">
            <div class="flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-sky-400 to-blue-500 shadow-sm">
                    <flux:icon name="bell" class="size-4 text-white" />
                </div>
                <div>
                    <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
                    @if ($unread_count > 0)
                        <p class="text-xs font-medium text-sky-600 dark:text-sky-400">
                            <span class="inline-flex items-center gap-1">
                                <span class="size-1.5 rounded-full bg-sky-500"></span>
                                {{ __(':count unread', ['count' => $unread_count]) }}
                            </span>
                        </p>
                    @else
                        <p class="text-xs text-neutral-400">{{ __('All caught up') }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-1">
                <template x-if="!showClearConfirm">
                    <div class="flex items-center gap-1">
                        @if (count($notifications) > 0)
                            <button wire:click="markAllAsRead" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-sky-600 transition hover:bg-sky-50 dark:text-sky-400 dark:hover:bg-sky-900/20" title="{{ __('Mark all as read') }}">
                                <flux:icon name="check" class="size-3.5" />
                            </button>
                            <button x-on:click="showClearConfirm = true" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-500 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20" title="{{ __('Clear all') }}">
                                <flux:icon name="trash" class="size-3.5" />
                            </button>
                        @endif
                        <button x-on:click="open = false; $wire.closePanel()" class="flex size-7 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-white">
                            <flux:icon name="x-mark" class="size-4" />
                        </button>
                    </div>
                </template>
                <template x-if="showClearConfirm">
                    <div class="flex items-center gap-1.5 rounded-lg bg-red-50 px-2 py-1 dark:bg-red-900/20">
                        <span class="text-xs font-medium text-red-600 dark:text-red-400">{{ __('Clear all?') }}</span>
                        <button wire:click="clearAll" x-on:click="showClearConfirm = false" class="rounded-md bg-red-500 px-2 py-0.5 text-xs font-semibold text-white transition hover:bg-red-600">
                            {{ __('Yes') }}
                        </button>
                        <button x-on:click="showClearConfirm = false" class="rounded-md bg-neutral-200 px-2 py-0.5 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-300 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500">
                            {{ __('No') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = is_null($notification->read_at);
                    $type = $data['type'] ?? 'default';
                    $color = $data['color'] ?? 'blue';
                    $icon = $iconMap[$type] ?? ($data['icon'] ?? 'bell');
                    $title = $data['title'] ?? 'Notification';
                    $description = $data['description'] ?? '';
                    $actionUrl = $data['action_url'] ?? '#';
                    $c = $colorMap[$color] ?? $colorMap['blue'];
                @endphp
                <div
                    class="group relative flex gap-3 border-b border-neutral-100 px-5 py-3.5 transition {{ $isUnread ? $c['tint'] : $c['hover'] }} dark:border-neutral-700/50"
                    wire:key="{{ $notification->id }}"
                >
                    @if ($isUnread)
                        <span class="absolute left-0 top-0 h-full w-0.5 {{ $c['border'] }}"></span>
                        <span class="absolute left-1.5 top-3.5 size-1.5 rounded-full {{ $c['dot'] }}"></span>
                    @endif

                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $isUnread ? $c['bg'] : 'bg-neutral-100 dark:bg-neutral-800' }} shadow-sm">
                        <flux:icon name="{{ $icon }}" class="size-4 {{ $isUnread ? $c['text'] : 'text-neutral-400 dark:text-neutral-500' }}" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <p class="text-sm font-semibold {{ $isUnread ? 'text-neutral-900 dark:text-white' : 'text-neutral-600 dark:text-neutral-400' }}">
                                    {{ $title }}
                                </p>
                                @if ($isUnread)
                                    <span class="size-1.5 rounded-full bg-sky-400"></span>
                                @endif
                            </div>
                            <span class="shrink-0 whitespace-nowrap text-[10px] font-medium {{ $isUnread ? 'text-neutral-400' : 'text-neutral-300 dark:text-neutral-500' }}">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-0.5 text-xs leading-relaxed {{ $isUnread ? 'text-neutral-600 dark:text-neutral-300' : 'text-neutral-400 dark:text-neutral-500' }} line-clamp-2">{{ $description }}</p>

                        <div class="mt-2 flex items-center gap-2">
                            @if ($actionUrl && $actionUrl !== '#')
                                <a
                                    href="{{ $actionUrl }}"
                                    wire:navigate
                                    x-on:click="open = false; $wire.closePanel()"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold {{ $isUnread ? $c['badge'] : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700' }} transition"
                                >
                                    {{ __('View') }}
                                    <flux:icon name="arrow-right" class="size-3" />
                                </a>
                            @endif
                            @if ($isUnread)
                                <button wire:click="markAsRead('{{ $notification->id }}')" class="rounded-md px-2 py-0.5 text-xs font-medium text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                                    {{ __('Dismiss') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center py-16 text-center">
                    <div class="flex size-16 items-center justify-center rounded-full bg-gradient-to-br from-sky-100 to-blue-100 shadow-inner dark:from-sky-900/30 dark:to-blue-900/30">
                        <flux:icon name="bell" class="size-7 text-sky-400 dark:text-sky-500" />
                    </div>
                    <flux:heading class="mt-5 text-neutral-500">{{ __('No notifications yet') }}</flux:heading>
                    <p class="mt-1 max-w-xs text-xs leading-relaxed text-neutral-400">{{ __('Notifications will appear here when you create invoices, record payments, or perform other actions.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
