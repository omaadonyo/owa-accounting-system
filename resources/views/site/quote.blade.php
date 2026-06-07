<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Akatabo') }} — Request Quote</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, .3); border-radius: 3px; }
        input::-webkit-inner-spin-button, input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-zinc-950 font-sans text-white antialiased">

    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-48 -top-48 size-[500px] rounded-full bg-indigo-500/8 blur-[140px]"></div>
        <div class="absolute -bottom-48 right-1/4 size-[400px] rounded-full bg-amber-500/6 blur-[120px]"></div>
    </div>

    <nav class="border-b border-zinc-800/50 bg-zinc-950/80 backdrop-blur-xl">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4 lg:px-10">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600">
                    <svg class="size-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                </div>
                <span class="text-base font-bold tracking-tight">{{ config('app.name', 'Akatabo') }}</span>
            </a>
            <a href="{{ route('site.index') }}" class="text-sm text-zinc-400 transition hover:text-white">&larr; Back to site</a>
        </div>
    </nav>

    @php
        $unitPrice = $type === 'fabric' ? $item->selling_price_per_meter : $item->selling_price;
        $unitLabel = $type === 'fabric' ? 'meters' : ($item->unit ?? 'units');
        $quantityLabel = $type === 'fabric' ? 'Length (meters)' : 'Quantity';
        $quantityPlaceholder = $type === 'fabric' ? 'e.g. 5' : 'e.g. 2';
    @endphp

    <div class="mx-auto max-w-5xl px-6 py-12 lg:px-10">

        @if (session('success'))
            <div class="mb-8 rounded-2xl border border-emerald-800/40 bg-emerald-900/20 p-6 text-center backdrop-blur-sm">
                <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-full bg-emerald-500/20">
                    <svg class="size-7 text-emerald-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h2 class="text-lg font-semibold text-emerald-300">Quotation Request Submitted!</h2>
                <p class="mt-1 text-sm text-zinc-400">{{ session('success') }}</p>
                <a href="{{ route('site.index') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-400 transition hover:text-indigo-300">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
                    Browse more items
                </a>
            </div>
        @endif

        <div class="grid gap-10 lg:grid-cols-5 lg:gap-12">

            {{-- Left: Item Preview --}}
            <div class="lg:col-span-2">
                <div class="sticky top-24">
                    <div class="overflow-hidden rounded-2xl border border-zinc-800/60 bg-zinc-900/40 backdrop-blur-sm">
                        <div class="aspect-[4/3] overflow-hidden bg-zinc-800">
                            @if ($item->image && Storage::disk('public')->exists($item->image))
                                <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}" class="size-full object-cover" />
                            @else
                                <div class="flex size-full items-center justify-center bg-gradient-to-br from-zinc-800 to-zinc-900">
                                    @if ($type === 'fabric')
                                        <svg class="size-16 text-zinc-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                                    @else
                                        <svg class="size-16 text-zinc-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" x2="21" y1="6" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-black/30 px-2 py-0.5 text-[10px] font-medium text-zinc-400 backdrop-blur-sm">{{ $type === 'fabric' ? 'Fabric' : 'Product' }}</span>
                                @if ($item->business)
                                    <span class="text-[10px] text-zinc-600">{{ $item->business->name }}</span>
                                @endif
                            </div>
                            <h2 class="mt-2 text-xl font-bold text-white">{{ $item->name }}</h2>
                            @if ($type === 'fabric' && $item->color)
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="size-3 rounded-full" style="background-color: {{ $item->color }};"></span>
                                    <span class="text-xs text-zinc-400">{{ $item->color }}</span>
                                </div>
                            @endif
                            <div class="mt-4 flex items-baseline gap-1.5">
                                <span class="text-2xl font-bold text-white">UGX {{ number_format($unitPrice, 0) }}</span>
                                <span class="text-sm text-zinc-500">/ {{ $type === 'fabric' ? 'meter' : ($item->unit ?? 'unit') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Quote Form --}}
            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-zinc-800/60 bg-zinc-900/40 p-6 backdrop-blur-sm sm:p-8">
                    <h2 class="text-lg font-semibold text-white">Request a Quotation</h2>
                    <p class="mt-1 text-sm text-zinc-500">Fill in your details and quantity below. The total updates automatically.</p>

                    <form method="POST" action="{{ route('site.submit') }}" class="mt-6 space-y-5" x-data="{
                        pricePerUnit: {{ $unitPrice }},
                        quantity: '',
                        get total() {
                            return (parseFloat(this.quantity) || 0) * this.pricePerUnit;
                        },
                        get isValid() {
                            return parseFloat(this.quantity) > 0;
                        }
                    }">
                        @csrf
                        <input type="hidden" name="item_type" value="{{ $type }}">
                        <input type="hidden" name="item_id" value="{{ $item->id }}">

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-zinc-300">Your Name <span class="text-red-400">*</span></label>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                                    class="w-full rounded-xl border border-zinc-700/60 bg-zinc-800/40 px-4 py-2.5 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-indigo-500/50 focus:bg-zinc-800/80 focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder="e.g. Jane Doe" />
                                @error('customer_name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-zinc-300">Email <span class="text-red-400">*</span></label>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" required
                                    class="w-full rounded-xl border border-zinc-700/60 bg-zinc-800/40 px-4 py-2.5 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-indigo-500/50 focus:bg-zinc-800/80 focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder="jane@example.com" />
                                @error('customer_email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-zinc-300">Phone</label>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}"
                                    class="w-full rounded-xl border border-zinc-700/60 bg-zinc-800/40 px-4 py-2.5 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-indigo-500/50 focus:bg-zinc-800/80 focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder="+256 700 000 000" />
                                @error('customer_phone') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="border-t border-zinc-800/50 pt-5">
                            <h3 class="text-sm font-semibold text-zinc-300">{{ $quantityLabel }}</h3>
                            <p class="text-xs text-zinc-600">Enter the {{ $unitLabel }} you need.</p>

                            <div class="mt-4">
                                <label class="mb-1.5 block text-sm font-medium text-zinc-300">{{ $quantityLabel }} <span class="text-red-400">*</span></label>
                                <input type="number" name="quantity" x-model="quantity" step="0.01" min="0.01" required
                                    class="w-full rounded-xl border border-zinc-700/60 bg-zinc-800/40 px-4 py-2.5 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-indigo-500/50 focus:bg-zinc-800/80 focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder="{{ $quantityPlaceholder }}" />
                                @error('quantity') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="mt-5 rounded-xl border border-zinc-700/40 bg-zinc-800/30 p-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-400">UGX {{ number_format($unitPrice, 0) }} × <span x-text="parseFloat(quantity) || 0" class="font-mono"></span> {{ $unitLabel }}</span>
                                    <span class="text-zinc-500">=</span>
                                    <span class="text-lg font-bold text-indigo-400" x-text="'UGX ' + total.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})">UGX 0</span>
                                </div>
                                <template x-if="!isValid">
                                    <p class="mt-2 text-xs text-zinc-600">Enter a quantity to see the estimated total.</p>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-zinc-300">Additional Notes</label>
                            <textarea name="customer_message" rows="3"
                                class="w-full rounded-xl border border-zinc-700/60 bg-zinc-800/40 px-4 py-2.5 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-indigo-500/50 focus:bg-zinc-800/80 focus:ring-2 focus:ring-indigo-500/20"
                                placeholder="Any specific requirements...">{{ old('customer_message') }}</textarea>
                            @error('customer_message') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit"
                            class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:from-indigo-500 hover:to-indigo-400 hover:shadow-indigo-500/30">
                            Submit Quotation Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="border-t border-zinc-800/50 px-6 py-6 lg:px-10">
        <div class="mx-auto flex max-w-5xl items-center justify-between">
            <p class="text-xs text-zinc-600">&copy; {{ date('Y') }} {{ config('app.name', 'Akatabo') }}. All rights reserved.</p>
            <a href="{{ route('site.index') }}" class="text-xs text-zinc-600 transition hover:text-zinc-400">All items</a>
        </div>
    </footer>

</body>
</html>
