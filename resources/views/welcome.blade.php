@php
    use App\Models\Business;
    use App\Models\Customer;
    use App\Models\Invoice;
    use App\Models\ProductService;

    $totalBusinesses = Business::count();
    $totalCustomers = Customer::count();
    $totalInvoices = Invoice::count();
    $totalProducts = ProductService::count();
    $revenue = Invoice::sum('paid_amount');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Akatabo') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>html{scrollbar-width:none}body{overflow:hidden}</style>
</head>
<body class="flex h-screen w-screen flex-col bg-zinc-950 font-sans text-white antialiased">

    {{-- Background blur --}}
    <div class="pointer-events-none fixed inset-0 -z-10">
        <div class="absolute -left-32 -top-32 size-[400px] rounded-full bg-indigo-500/15 blur-[120px]"></div>
        <div class="absolute -right-32 bottom-0 size-[400px] rounded-full bg-amber-500/10 blur-[120px]"></div>
        <div class="absolute bottom-1/4 left-1/4 size-[300px] rounded-full bg-emerald-500/8 blur-[100px]"></div>
    </div>

    {{-- Top bar --}}
    <div class="flex shrink-0 items-center justify-between border-b border-zinc-800/50 px-6 py-4 lg:px-10">
        <div class="flex items-center gap-2.5">
            <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-500/10">
                <svg class="size-5 text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="12" x="3" y="7" rx="2"/><path d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/><path d="M12 12v4"/><path d="M8 16h8"/></svg>
            </div>
            <span class="text-base font-bold tracking-tight">{{ config('app.name', 'Akatabo') }}</span>
        </div>
        <div class="flex items-center gap-3">
            @if (Route::has('login'))
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium transition hover:bg-indigo-500">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-zinc-400 transition hover:text-white">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium transition hover:bg-indigo-500">Register</a>
                @endauth
            @endif
        </div>
    </div>

    {{-- Main content --}}
    <div class="flex min-h-0 flex-1 flex-col items-center justify-center px-6 lg:px-10">
        <div class="flex w-full max-w-5xl flex-col items-center gap-8 lg:flex-row lg:items-start lg:gap-16">

            {{-- Left: Hero --}}
            <div class="flex shrink-0 flex-col justify-center lg:w-[440px]">
                <h1 class="text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
                    Your business,
                    <span class="block bg-gradient-to-r from-indigo-400 via-amber-300 to-emerald-400 bg-clip-text text-transparent">one dashboard</span>
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-zinc-500">
                    Inventory, quotations, invoices, payments, and customers — managed in a clean, professional interface.
                </p>
                <div class="mt-6 flex items-center gap-3">
                    <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500">Get started</a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-zinc-700 bg-zinc-800/50 px-6 py-2.5 text-sm font-semibold text-zinc-300 backdrop-blur-sm transition hover:border-zinc-600 hover:text-white">Sign in</a>
                </div>
            </div>

            {{-- Right: Stats grid --}}
            <div class="grid flex-1 grid-cols-2 gap-3">
                <div class="rounded-xl border border-zinc-800/60 bg-zinc-900/40 p-5 backdrop-blur-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-500/10">
                            <svg class="size-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="12" x="3" y="7" rx="2"/><path d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/><path d="M12 12v4"/><path d="M8 16h8"/></svg>
                        </div>
                        <span class="text-xs font-medium text-zinc-500">Businesses</span>
                    </div>
                    <p class="mt-3 text-2xl font-bold">{{ number_format($totalBusinesses) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-800/60 bg-zinc-900/40 p-5 backdrop-blur-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10">
                            <svg class="size-4 text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <span class="text-xs font-medium text-zinc-500">Customers</span>
                    </div>
                    <p class="mt-3 text-2xl font-bold">{{ number_format($totalCustomers) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-800/60 bg-zinc-900/40 p-5 backdrop-blur-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-amber-500/10">
                            <svg class="size-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                        </div>
                        <span class="text-xs font-medium text-zinc-500">Products</span>
                    </div>
                    <p class="mt-3 text-2xl font-bold">{{ number_format($totalProducts) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-800/60 bg-zinc-900/40 p-5 backdrop-blur-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-rose-500/10">
                            <svg class="size-4 text-rose-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                        </div>
                        <span class="text-xs font-medium text-zinc-500">Invoices</span>
                    </div>
                    <p class="mt-3 text-2xl font-bold">{{ number_format($totalInvoices) }}</p>
                </div>

                {{-- Revenue bar --}}
                <div class="col-span-2 rounded-xl border border-zinc-800/60 bg-zinc-900/40 p-5 backdrop-blur-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-500/10">
                                <svg class="size-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </div>
                            <span class="text-xs font-medium text-zinc-500">Revenue Tracked</span>
                        </div>
                        <p class="text-xl font-bold text-emerald-400">UGX {{ number_format($revenue, 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="flex shrink-0 items-center justify-between border-t border-zinc-800/50 px-6 py-3 text-xs text-zinc-600 lg:px-10">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'Akatabo') }}</span>
        <span>Built with Laravel & Flux</span>
    </div>
</body>
</html>
