<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Akatabo') }} — Premium Fabrics</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, .3); border-radius: 3px; }

        .fade-up {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .6s ease, transform .6s ease;
        }
        .fade-up.in-view {
            opacity: 1;
            transform: translateY(0);
        }

        .shimmer {
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.04) 50%, transparent 100%);
            background-size: 200% 100%;
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .card-hover {
            transition: transform .35s cubic-bezier(.25,.46,.45,.94), box-shadow .35s ease;
        }
        .card-hover:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 60px -12px rgba(99, 102, 241, .2);
        }

        .price-input {
            -moz-appearance: textfield;
        }
        .price-input::-webkit-inner-spin-button,
        .price-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body class="bg-zinc-950 font-sans text-white antialiased">

    {{-- Background effects --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-48 -top-48 size-[600px] rounded-full bg-indigo-500/10 blur-[150px]"></div>
        <div class="absolute -right-48 top-1/3 size-[500px] rounded-full bg-amber-500/8 blur-[140px]"></div>
        <div class="absolute -bottom-48 left-1/3 size-[400px] rounded-full bg-emerald-500/6 blur-[120px]"></div>
    </div>

    {{-- Nav --}}
    <nav class="sticky top-0 z-50 border-b border-zinc-800/50 bg-zinc-950/80 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-10">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600">
                    <svg class="size-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                </div>
                <span class="text-base font-bold tracking-tight">{{ config('app.name', 'Akatabo') }}</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="#fabrics" class="hidden text-sm text-zinc-400 transition hover:text-white sm:block">Fabrics</a>
                <a href="#how-it-works" class="hidden text-sm text-zinc-400 transition hover:text-white sm:block">How it works</a>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium transition hover:bg-indigo-500">Dashboard</a>
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="relative overflow-hidden px-6 pb-28 pt-20 lg:px-10 lg:pb-36 lg:pt-28">
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-col items-center text-center">
                <div class="mb-6 inline-flex items-center gap-1.5 rounded-full border border-zinc-700/50 bg-zinc-800/30 px-4 py-1.5 text-xs font-medium text-zinc-400 backdrop-blur-sm">
                    <span class="flex size-2 rounded-full bg-emerald-400"></span>
                    Premium fabrics sourced worldwide
                </div>
                <h1 class="max-w-4xl text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
                    Fabrics that
                    <span class="bg-gradient-to-r from-indigo-400 via-amber-300 to-emerald-400 bg-clip-text text-transparent">elevate</span>
                    <br class="hidden sm:block" />
                    every creation
                </h1>
                <p class="mt-5 max-w-xl text-base leading-relaxed text-zinc-500">
                    Browse our curated collection of premium fabrics. Select your favourite, enter your measurements, and receive a quotation instantly.
                </p>
                <div class="mt-8 flex items-center gap-4">
                    <a href="#fabrics" class="rounded-xl bg-indigo-600 px-7 py-3 text-sm font-semibold shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 hover:shadow-indigo-500/30">Browse fabrics</a>
                    <a href="#how-it-works" class="rounded-xl border border-zinc-700 bg-zinc-800/40 px-7 py-3 text-sm font-semibold text-zinc-300 backdrop-blur-sm transition hover:border-zinc-500 hover:text-white">How it works</a>
                </div>
            </div>

            {{-- Floating stats --}}
            <div class="mt-16 grid grid-cols-2 gap-3 sm:gap-5">
                <div class="rounded-2xl border border-zinc-800/50 bg-zinc-900/30 p-5 backdrop-blur-sm">
                    <p class="text-2xl font-bold text-white sm:text-3xl">{{ $fabrics->count() }}+</p>
                    <p class="mt-1 text-xs text-zinc-500">Premium Fabrics</p>
                </div>
                <div class="rounded-2xl border border-zinc-800/50 bg-zinc-900/30 p-5 backdrop-blur-sm">
                    <p class="text-2xl font-bold text-transparent sm:text-3xl"><span class="bg-gradient-to-r from-amber-300 to-emerald-400 bg-clip-text text-transparent">Instant</span></p>
                    <p class="mt-1 text-xs text-zinc-500">Quotation Response</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Fabrics Grid --}}
    <section id="fabrics" class="px-6 pb-28 lg:px-10">
        <div class="mx-auto max-w-6xl">
            <div class="mb-10 text-center">
                <h2 class="text-2xl font-bold sm:text-3xl">Our <span class="bg-gradient-to-r from-indigo-400 to-amber-300 bg-clip-text text-transparent">Collection</span></h2>
                <p class="mt-2 text-sm text-zinc-500">Each fabric is handpicked for quality. Click "Request Quote" to get your custom price.</p>
            </div>

            @if ($fabrics->isEmpty())
                <div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-16 text-center backdrop-blur-sm">
                    <p class="text-zinc-400">No fabrics available yet. Check back soon!</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($fabrics as $fabric)
                        <div class="card-hover group relative overflow-hidden rounded-2xl border border-zinc-800/60 bg-zinc-900/40 backdrop-blur-sm">
                            {{-- Image --}}
                            <div class="relative aspect-[4/3] overflow-hidden bg-zinc-800">
                                @if ($fabric->image && Storage::disk('public')->exists($fabric->image))
                                    <img src="{{ Storage::url($fabric->image) }}" alt="{{ $fabric->name }}" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
                                @else
                                    <div class="flex size-full items-center justify-center bg-gradient-to-br from-zinc-800 to-zinc-900">
                                        <svg class="size-12 text-zinc-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                                    </div>
                                @endif
                                @if ($fabric->color)
                                    <div class="absolute left-3 top-3 flex items-center gap-1.5 rounded-full bg-black/50 px-2.5 py-1 text-xs text-white backdrop-blur-sm">
                                        <span class="size-2.5 rounded-full" style="background-color: {{ $fabric->color }};"></span>
                                        {{ $fabric->color }}
                                    </div>
                                @endif
                                @if ($fabric->remaining_meters !== null && $fabric->remaining_meters <= $fabric->low_stock_threshold)
                                    <div class="absolute right-3 top-3 rounded-full bg-amber-500/20 px-2.5 py-1 text-xs font-medium text-amber-400 backdrop-blur-sm">
                                        Low stock
                                    </div>
                                @endif
                            </div>

                            {{-- Details --}}
                            <div class="p-5">
                                <div class="mb-1 flex items-start justify-between gap-2">
                                    <h3 class="text-base font-semibold text-white">{{ $fabric->name }}</h3>
                                    @if ($fabric->roll_code)
                                        <span class="shrink-0 font-mono text-[10px] text-zinc-600">{{ $fabric->roll_code }}</span>
                                    @endif
                                </div>

                                <div class="mt-4 flex items-end justify-between">
                                    <div>
                                        <p class="text-lg font-bold text-white">UGX {{ number_format($fabric->selling_price_per_meter, 0) }}</p>
                                        <p class="text-xs text-zinc-500">per meter</p>
                                    </div>
                                </div>

                                <a href="{{ route('fabrics.quote', $fabric) }}"
                                   class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600/10 px-4 py-2.5 text-sm font-medium text-indigo-400 ring-1 ring-indigo-600/20 transition hover:bg-indigo-600 hover:text-white hover:shadow-lg hover:shadow-indigo-600/20">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    Request Quote
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="px-6 pb-28 lg:px-10">
        <div class="mx-auto max-w-6xl">
            <div class="mb-12 text-center">
                <h2 class="text-2xl font-bold sm:text-3xl"><span class="bg-gradient-to-r from-emerald-400 to-indigo-400 bg-clip-text text-transparent">How it</span> works</h2>
                <p class="mt-2 text-sm text-zinc-500">Three simple steps to get your fabric quotation.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="relative rounded-2xl border border-zinc-800/50 bg-zinc-900/30 p-6 backdrop-blur-sm">
                    <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-indigo-500/10">
                        <span class="text-lg font-bold text-indigo-400">01</span>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Browse & Select</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500">Explore our curated collection of premium fabrics and choose the one that fits your project.</p>
                </div>

                <div class="relative rounded-2xl border border-zinc-800/50 bg-zinc-900/30 p-6 backdrop-blur-sm">
                    <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-amber-500/10">
                        <span class="text-lg font-bold text-amber-400">02</span>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Enter Measurements</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500">Tell us how many meters you need. Our system automatically calculates the price for you.</p>
                </div>

                <div class="relative rounded-2xl border border-zinc-800/50 bg-zinc-900/30 p-6 backdrop-blur-sm">
                    <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-emerald-500/10">
                        <span class="text-lg font-bold text-emerald-400">03</span>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Get Your Quote</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500">Submit your request and we'll send the official quotation to your email right away.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-zinc-800/50 px-6 py-6 lg:px-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between">
            <p class="text-xs text-zinc-600">&copy; {{ date('Y') }} {{ config('app.name', 'Akatabo') }}. All rights reserved.</p>
            <p class="text-xs text-zinc-600">Built with care</p>
        </div>
    </footer>

    {{-- Intersection Observer for fade-up --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>
