<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Akatabo') }} — Pricing</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, .3); border-radius: 3px; }
    </style>
</head>
<body class="bg-zinc-950 font-sans text-white antialiased">

    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-48 -top-48 size-[600px] rounded-full bg-indigo-500/10 blur-[150px]"></div>
        <div class="absolute -right-48 top-1/3 size-[500px] rounded-full bg-amber-500/8 blur-[140px]"></div>
    </div>

    <nav class="sticky top-0 z-50 border-b border-zinc-800/50 bg-zinc-950/80 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-10">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600">
                    <svg class="size-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                </div>
                <span class="text-base font-bold tracking-tight">{{ config('app.name', 'Akatabo') }}</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('site.index') }}" class="text-sm text-zinc-400 transition hover:text-white">Marketplace</a>
                <a href="{{ route('site.pricing') }}" class="text-sm text-indigo-400 transition hover:text-indigo-300">Pricing</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium transition hover:bg-indigo-500">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-zinc-400 transition hover:text-white">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium transition hover:bg-indigo-500">Get started</a>
                @endauth
            </div>
        </div>
    </nav>

    <section class="px-6 py-20 lg:px-10 lg:py-28">
        <div class="mx-auto max-w-6xl">
            <div class="text-center">
                <div class="mb-6 inline-flex items-center gap-1.5 rounded-full border border-zinc-700/50 bg-zinc-800/30 px-4 py-1.5 text-xs font-medium text-zinc-400 backdrop-blur-sm">
                    <span class="flex size-2 rounded-full bg-emerald-400"></span>
                    Simple, transparent pricing
                </div>
                <h1 class="text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
                    Choose the plan that<br>
                    <span class="bg-gradient-to-r from-indigo-400 to-amber-300 bg-clip-text text-transparent">fits your business</span>
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-zinc-500">
                    Start free and upgrade as you grow. No hidden fees, no surprises.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-center gap-2">
                <button onclick="switchBilling('monthly')" id="monthly-btn" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition">Monthly</button>
                <button onclick="switchBilling('yearly')" id="yearly-btn" class="rounded-lg px-5 py-2 text-sm font-medium text-zinc-400 transition hover:text-white">Yearly <span class="ml-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] text-emerald-400">Save up to 17%</span></button>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3" id="pricing-grid">
                @foreach ($plans as $plan)
                    @php
                        $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
                    @endphp
                    <div class="relative flex flex-col rounded-2xl border border-zinc-800/60 bg-zinc-900/40 p-6 backdrop-blur-sm transition hover:border-zinc-700/60 {{ $plan->slug === 'enterprise' ? 'ring-1 ring-indigo-500/20' : '' }}" data-monthly="{{ $plan->price_monthly }}" data-yearly="{{ $plan->price_yearly }}">
                        @if ($plan->slug === 'enterprise')
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600 px-4 py-0.5 text-xs font-semibold text-white shadow-lg shadow-indigo-500/25">Most Popular</div>
                        @endif
                        <h3 class="text-lg font-bold text-white">{{ $plan->name }}</h3>
                        <p class="mt-1 text-xs text-zinc-500">{{ $plan->description }}</p>
                        <div class="mt-4 flex items-baseline gap-1">
                            @if ($plan->price_monthly > 0)
                                <span class="price-amount text-3xl font-bold text-white" data-monthly="{{ number_format($plan->price_monthly) }}" data-yearly="{{ number_format($plan->price_yearly) }}">UGX {{ number_format($plan->price_monthly) }}</span>
                                <span class="price-period text-sm text-zinc-500" data-monthly="/mo" data-yearly="/yr">/mo</span>
                            @else
                                <span class="text-3xl font-bold text-white">Free</span>
                            @endif
                        </div>
                        <ul class="mt-6 flex-1 space-y-2.5">
                            @foreach ($features as $feature)
                                <li class="flex items-start gap-2 text-sm text-zinc-400">
                                    <svg class="mt-0.5 size-4 shrink-0 text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                        @auth
                            <a href="{{ route('billing') }}" class="mt-6 flex w-full items-center justify-center rounded-xl bg-indigo-600 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                {{ $plan->price_monthly > 0 ? 'Upgrade' : 'Get started' }}
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="mt-6 flex w-full items-center justify-center rounded-xl bg-indigo-600 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                {{ $plan->price_monthly > 0 ? 'Start free trial' : 'Get started — it\'s free' }}
                            </a>
                        @endauth
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-zinc-800/50 px-6 py-16 lg:px-10">
        <div class="mx-auto max-w-6xl">
            <div class="mb-10 text-center">
                <h2 class="text-2xl font-bold sm:text-3xl">Frequently Asked Questions</h2>
            </div>
            <div class="mx-auto grid max-w-3xl gap-4">
                @foreach ([
                    ['q' => 'Can I switch plans anytime?', 'a' => 'Yes, you can upgrade or downgrade at any time. Upgrades take effect immediately, while downgrades apply at the end of your billing cycle.'],
                    ['q' => 'What payment methods do you accept?', 'a' => 'We accept Mobile Money (MTN & Airtel), bank transfers, and cash. Credit card payments via Flutterwave are coming soon.'],
                    ['q' => 'Is there a free trial?', 'a' => 'The Free plan is available forever with no time limit. Upgrade to Business or Enterprise whenever you need more capacity.'],
                    ['q' => 'What happens if I exceed my plan limits?', 'a' => 'You\'ll be notified when you\'re approaching your limits. To continue creating, simply upgrade to a higher plan.'],
                    ['q' => 'Can I cancel my subscription?', 'a' => 'Yes, you can cancel anytime. Your access continues until the end of the current billing period, then you\'ll be downgraded to the Free plan.'],
                ] as $faq)
                    <details class="group rounded-xl border border-zinc-800/60 bg-zinc-900/30 p-4 transition hover:border-zinc-700/60">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-medium text-white">
                            {{ $faq['q'] }}
                            <svg class="size-4 text-zinc-500 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-zinc-500">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <footer class="border-t border-zinc-800/50 px-6 py-6 lg:px-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between">
            <p class="text-xs text-zinc-600">&copy; {{ date('Y') }} {{ config('app.name', 'Akatabo') }}. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('site.index') }}" class="text-xs text-zinc-600 transition hover:text-zinc-400">Marketplace</a>
                <a href="{{ route('site.pricing') }}" class="text-xs text-indigo-500 transition hover:text-indigo-400">Pricing</a>
            </div>
        </div>
    </footer>

    <script>
        let currentBilling = 'monthly';
        function switchBilling(cycle) {
            currentBilling = cycle;
            document.getElementById('monthly-btn').className = cycle === 'monthly' ? 'rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition' : 'rounded-lg px-5 py-2 text-sm font-medium text-zinc-400 transition hover:text-white';
            document.getElementById('yearly-btn').className = cycle === 'yearly' ? 'rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition' : 'rounded-lg px-5 py-2 text-sm font-medium text-zinc-400 transition hover:text-white';
            document.querySelectorAll('.price-amount').forEach(el => {
                el.textContent = 'UGX ' + el.dataset[cycle];
            });
            document.querySelectorAll('.price-period').forEach(el => {
                el.textContent = el.dataset[cycle];
            });
        }
    </script>
</body>
</html>
