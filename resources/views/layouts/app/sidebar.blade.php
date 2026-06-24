<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky :collapsible="true" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @can('manage-customers')
                        <flux:sidebar.item icon="users" :href="route('customers')" :current="request()->routeIs('customers')" wire:navigate>
                            {{ __('Customers') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('manage-inventory')
                        <flux:sidebar.item icon="box" :href="route('inventory')" :current="request()->routeIs('inventory')" wire:navigate>
                            {{ __('Inventory') }}
                        </flux:sidebar.item>
                    @endcan

                    <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>
                        {{ __('Reports') }}
                    </flux:sidebar.item>

                    @can('manage-users')
                        <flux:sidebar.item icon="user-group" :href="route('users')" :current="request()->routeIs('users')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('superadmin')
                        <flux:sidebar.item icon="shield-check" :href="route('superadmin')" :current="request()->routeIs('superadmin')" wire:navigate>
                            {{ __('Superadmin') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Sales')" class="grid">
                    <flux:sidebar.item icon="file-text" :href="route('quotations')" :current="request()->routeIs('quotations*')" wire:navigate>
                        {{ __('Quotations') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="file-invoice" :href="route('invoices')" :current="request()->routeIs('invoices*')" wire:navigate>
                        {{ __('Invoices') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="credit-card" :href="route('payments')" :current="request()->routeIs('payments')" wire:navigate>
                        {{ __('Payments') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="document-text" :href="route('customer-quotations')" :current="request()->routeIs('customer-quotations')" wire:navigate>
                        {{ __('Customer Requests') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Settings')" class="grid">
                    @can('manage-business')
                        <flux:sidebar.item icon="building" :href="route('business.edit')" :current="request()->routeIs('business.edit')" wire:navigate>
                            {{ __('Business') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('manage-business')
                        <flux:sidebar.item icon="shopping-cart" :href="route('store')" :current="request()->routeIs('store')" wire:navigate>
                            {{ __('Store') }}
                        </flux:sidebar.item>
                    @endcan

                    <flux:sidebar.item icon="currency-dollar" :href="route('billing')" :current="request()->routeIs('billing')" wire:navigate>
                        {{ __('Subscription') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="server-stack" :href="route('backup.edit')" :current="request()->routeIs('backup.edit')" wire:navigate>
                        {{ __('Backup & Restore') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="cog" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                        {{ __('Profile') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/omaadonyo/akatabo-improved-system" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="{{ url('docs/index.html') }}" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            @php $switchable = auth()->user()->businesses; @endphp
            @if ($switchable->isNotEmpty())
                <flux:dropdown position="bottom" align="start">
                    <flux:navbar.item icon="building-storefront" class="cursor-pointer text-sm">
                        {{ Str::limit(currentBusiness()?->name ?? '—', 18) }}
                    </flux:navbar.item>

                    <flux:menu class="min-w-56">
                        <flux:menu.radio.group>
                            <div class="px-2 py-1.5 text-xs font-medium text-neutral-500">{{ __('Switch Business') }}</div>
                            @foreach ($switchable as $biz)
                                <form method="POST" action="{{ route('business.switch', $biz) }}">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" class="w-full cursor-pointer">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold {{ session('active_business_id') == $biz->id ? 'bg-accent text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300' }}">
                                                {{ strtoupper(substr($biz->name, 0, 1)) }}
                                            </div>
                                            <div class="grid text-left text-xs">
                                                <span class="font-medium">{{ $biz->name }}</span>
                                                @if ($biz->email)
                                                    <span class="text-neutral-400">{{ $biz->email }}</span>
                                                @endif
                                            </div>
                                            @if (session('active_business_id') == $biz->id)
                                                <flux:icon.check class="ml-auto h-4 w-4 text-accent" />
                                            @endif
                                        </div>
                                    </flux:menu.item>
                                </form>
                            @endforeach
                            <flux:menu.separator />
                            @if (canAddBusiness())
                                <flux:menu.item :href="route('onboarding', ['add' => 1])" icon="plus" wire:navigate>
                                    {{ __('New Business') }}
                                </flux:menu.item>
                            @else
                                <flux:menu.item disabled icon="plus" class="opacity-50">
                                    {{ __('Business limit reached') }}
                                </flux:menu.item>
                            @endif
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:dropdown position="bottom" align="start">
                    <flux:navbar.item icon="building-storefront" class="cursor-pointer text-sm text-neutral-400">
                        {{ Str::limit(currentBusiness()?->name ?? '—', 18) }}
                    </flux:navbar.item>
                    <flux:menu class="min-w-56">
                        @if (canAddBusiness())
                            <flux:menu.item :href="route('onboarding', ['add' => 1])" icon="plus" wire:navigate>
                                {{ __('New Business') }}
                            </flux:menu.item>
                        @else
                            <flux:menu.item disabled icon="plus" class="opacity-50">
                                {{ __('Business limit reached') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            @endif

            <livewire:notification-bell />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        @can('manage-business')
                        <flux:menu.item :href="route('business.edit')" icon="building" wire:navigate>
                            {{ __('Business') }}
                        </flux:menu.item>
                    @endcan

                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Desktop Header -->
        <flux:header class="hidden lg:flex">
            <flux:sidebar.toggle icon="bars-2" inset="left" />

            @php $switchable = auth()->user()->businesses; @endphp
            @if ($switchable->count() > 1)
                <flux:dropdown position="bottom" align="start">
                    <flux:navbar.item icon="building-storefront" class="cursor-pointer text-sm">
                        {{ Str::limit(currentBusiness()?->name ?? '—', 24) }}
                    </flux:navbar.item>

                    <flux:menu class="min-w-56">
                        <flux:menu.radio.group>
                            <div class="px-2 py-1.5 text-xs font-medium text-neutral-500">{{ __('Switch Business') }}</div>
                            @foreach ($switchable as $biz)
                                <form method="POST" action="{{ route('business.switch', $biz) }}">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" class="w-full cursor-pointer">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold {{ session('active_business_id') == $biz->id ? 'bg-accent text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300' }}">
                                                {{ strtoupper(substr($biz->name, 0, 1)) }}
                                            </div>
                                            <div class="grid text-left text-xs">
                                                <span class="font-medium">{{ $biz->name }}</span>
                                                @if ($biz->email)
                                                    <span class="text-neutral-400">{{ $biz->email }}</span>
                                                @endif
                                            </div>
                                            @if (session('active_business_id') == $biz->id)
                                                <flux:icon.check class="ml-auto h-4 w-4 text-accent" />
                                            @endif
                                        </div>
                                    </flux:menu.item>
                                </form>
                            @endforeach
                            <flux:menu.separator />
                            @if (canAddBusiness())
                                <flux:menu.item :href="route('onboarding', ['add' => 1])" icon="plus" wire:navigate>
                                    {{ __('New Business') }}
                                </flux:menu.item>
                            @else
                                <flux:menu.item disabled icon="plus" class="opacity-50">
                                    {{ __('Business limit reached') }}
                                </flux:menu.item>
                            @endif
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:dropdown position="bottom" align="start">
                    <flux:navbar.item icon="building-storefront" class="cursor-pointer text-sm text-neutral-400">
                        {{ Str::limit(currentBusiness()?->name ?? '—', 24) }}
                    </flux:navbar.item>
                    <flux:menu class="min-w-56">
                        @if (canAddBusiness())
                            <flux:menu.item :href="route('onboarding', ['add' => 1])" icon="plus" wire:navigate>
                                {{ __('New Business') }}
                            </flux:menu.item>
                        @else
                            <flux:menu.item disabled icon="plus" class="opacity-50">
                                {{ __('Business limit reached') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            @endif

            <livewire:notification-bell />

            <flux:spacer />
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
