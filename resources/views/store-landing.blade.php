@php
    $font = $business->store_font ?? 'Inter';
    $primary = $business->store_primary_color ?? '#4f46e5';
    $accent = $business->store_accent_color ?? '#f59e0b';
    $inventoryItems = $business->storeProductsServices()->get();
    $fabrics = $business->storeFabrics()->get();
    $hasItems = $inventoryItems->isNotEmpty() || $fabrics->isNotEmpty();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $business->store_headline ?: $business->name }} — {{ $business->name }}</title>
    <meta name="description" content="{{ $business->store_subheadline ? strip_tags($business->store_subheadline) : $business->name }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $font) }}:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --store-primary: {{ $primary }};
            --store-accent: {{ $accent }};
            --store-font: '{{ $font }}', sans-serif;
        }
        body { font-family: var(--store-font); }
        .bg-store-primary { background-color: var(--store-primary); }
        .text-store-primary { color: var(--store-primary); }
        .ring-store-primary { --tw-ring-color: var(--store-primary); }
        .hover\:bg-store-primary-dark:hover { background-color: color-mix(in srgb, var(--store-primary) 85%, black); }
        .hover\:text-store-primary:hover { color: var(--store-primary); }
    </style>
</head>
<body class="bg-white text-neutral-900 antialiased">

    {{-- Navbar --}}
    <nav class="fixed left-0 right-0 top-0 z-50 border-b border-neutral-100 bg-white/80 backdrop-blur-lg">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="#" class="flex items-center gap-2">
                @if ($business->logo)
                    <img src="{{ asset('storage/' . $business->logo) }}" class="h-8 w-auto">
                @else
                    <span class="flex size-8 items-center justify-center rounded-lg font-bold text-white text-sm" style="background-color:var(--store-primary)">{{ substr($business->name, 0, 1) }}</span>
                @endif
                <span class="text-lg font-semibold">{{ $business->name }}</span>
            </a>
            <div class="flex items-center gap-6 text-sm font-medium text-neutral-600">
                @if ($hasItems)
                    <a href="#products" class="transition hover:text-store-primary">{{ __('Products') }}</a>
                @endif
                @if ($business->store_show_about && $business->store_about_text)
                    <a href="#about" class="transition hover:text-store-primary">{{ __('About') }}</a>
                @endif
                @if ($business->store_show_contact && ($business->store_contact_email || $business->store_contact_phone))
                    <a href="#contact" class="transition hover:text-store-primary">{{ __('Contact') }}</a>
                @endif
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="relative flex min-h-screen items-center overflow-hidden pt-16">
        @if ($business->store_hero_image)
            <div class="absolute inset-0">
                <img src="{{ asset('storage/' . $business->store_hero_image) }}" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-transparent"></div>
            </div>
        @else
            <div class="absolute inset-0" style="background:linear-gradient(135deg, color-mix(in srgb, var(--store-primary) 10%, white), white 50%, color-mix(in srgb, var(--store-accent) 8%, white))"></div>
        @endif
        <div class="relative mx-auto max-w-6xl px-6 py-24">
            <div class="max-w-2xl">
                <h1 class="text-5xl font-bold leading-tight tracking-tight md:text-7xl {{ $business->store_hero_image ? 'text-white' : '' }}">
                    {{ $business->store_headline ?: __('Welcome to :name', ['name' => $business->name]) }}
                </h1>
                @if ($business->store_subheadline)
                    <p class="mt-6 text-lg leading-relaxed md:text-xl {{ $business->store_hero_image ? 'text-white/80' : 'text-neutral-600' }}">
                        {{ $business->store_subheadline }}
                    </p>
                @endif
                @if ($hasItems)
                    <div class="mt-10 flex gap-4">
                        <a href="#products" class="inline-flex items-center gap-2 rounded-full px-8 py-3 text-sm font-semibold text-white shadow-lg transition hover:scale-105 active:scale-95" style="background-color:var(--store-primary)">
                            {{ __('Browse Products') }}
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Products --}}
    @if ($hasItems && $business->store_show_products)
        <section id="products" class="bg-neutral-50 px-6 py-24">
            <div class="mx-auto max-w-6xl">
                <div class="mb-14 text-center">
                    <span class="inline-block rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider" style="background-color:color-mix(in srgb, var(--store-primary) 12%, transparent);color:var(--store-primary)">{{ __('Our Collection') }}</span>
                    <h2 class="mt-4 text-4xl font-bold tracking-tight">{{ __('Featured Products') }}</h2>
                    <p class="mt-3 text-neutral-500">{{ __('Handpicked just for you') }}</p>
                </div>
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($inventoryItems as $item)
                        <div class="group relative overflow-hidden rounded-2xl bg-white shadow-sm transition hover:shadow-xl">
                            <div class="aspect-[4/5] overflow-hidden bg-neutral-100">
                                @if ($item->image)
                                    <img src="{{ asset('storage/' . $item->image) }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center text-neutral-300">
                                        <svg class="size-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <h3 class="text-lg font-semibold">{{ $item->name }}</h3>
                                @php $desc = $item->store_description ?: $item->description; @endphp
                                @if ($desc)
                                    <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 line-clamp-2">{{ $desc }}</p>
                                @endif
                                <div class="mt-4 flex items-center gap-2">
                                    <span class="text-xl font-bold" style="color:var(--store-primary)">{{ formatCurrency($item->selling_price) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @foreach ($fabrics as $fabric)
                        <div class="group relative overflow-hidden rounded-2xl bg-white shadow-sm transition hover:shadow-xl">
                            <div class="aspect-[4/5] overflow-hidden bg-neutral-100">
                                @if ($fabric->image)
                                    <img src="{{ asset('storage/' . $fabric->image) }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center text-neutral-300">
                                        <svg class="size-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <h3 class="text-lg font-semibold">{{ $fabric->name }}</h3>
                                @php $desc = $fabric->store_description ?: $fabric->color; @endphp
                                @if ($desc)
                                    <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 line-clamp-2">{{ $desc }}</p>
                                @endif
                                <div class="mt-4 flex items-center gap-2">
                                    <span class="text-xl font-bold" style="color:var(--store-primary)">{{ formatCurrency($fabric->selling_price_per_meter) }}<span class="text-sm font-normal text-neutral-400">/m</span></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- About --}}
    @if ($business->store_show_about && $business->store_about_text)
        <section id="about" class="px-6 py-24">
            <div class="mx-auto max-w-4xl text-center">
                <span class="inline-block rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider" style="background-color:color-mix(in srgb, var(--store-primary) 12%, transparent);color:var(--store-primary)">{{ __('Our Story') }}</span>
                <h2 class="mt-4 text-4xl font-bold tracking-tight">{{ __('About Us') }}</h2>
                <div class="mt-8 text-lg leading-relaxed text-neutral-600">
                    {{ $business->store_about_text }}
                </div>
            </div>
        </section>
    @endif

    {{-- Contact --}}
    @if ($business->store_show_contact && ($business->store_contact_email || $business->store_contact_phone))
        <section id="contact" class="px-6 py-24" style="background:linear-gradient(135deg, color-mix(in srgb, var(--store-primary) 5%, white), white)">
            <div class="mx-auto max-w-4xl text-center">
                <span class="inline-block rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider" style="background-color:color-mix(in srgb, var(--store-primary) 12%, transparent);color:var(--store-primary)">{{ __('Get in Touch') }}</span>
                <h2 class="mt-4 text-4xl font-bold tracking-tight">{{ __('Contact Us') }}</h2>
                <div class="mt-10 flex flex-wrap justify-center gap-8">
                    @if ($business->store_contact_email)
                        <a href="mailto:{{ $business->store_contact_email }}" class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white px-6 py-4 shadow-sm transition hover:shadow-md">
                            <svg class="size-5" style="color:var(--store-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-medium">{{ $business->store_contact_email }}</span>
                        </a>
                    @endif
                    @if ($business->store_contact_phone)
                        <a href="tel:{{ $business->store_contact_phone }}" class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white px-6 py-4 shadow-sm transition hover:shadow-md">
                            <svg class="size-5" style="color:var(--store-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span class="text-sm font-medium">{{ $business->store_contact_phone }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Footer --}}
    <footer class="border-t border-neutral-100 px-6 py-8 text-center text-sm text-neutral-400">
        <p>&copy; {{ date('Y') }} {{ $business->name }}. {{ __('All rights reserved.') }}</p>
    </footer>
</body>
</html>
