@props([
    'sidebar' => false,
])

@php
    $brandName = currentBusiness()?->name ?? __('Laravel Starter Kit');
    $words = explode(' ', $brandName);
    if (count($words) > 2) {
        $firstLine = implode(' ', array_slice($words, 0, 2));
        $secondLine = implode(' ', array_slice($words, 2));
        $brandName = $firstLine . "\n" . $secondLine;
    }
    $initials = collect($words)->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->implode('');
@endphp

@if($sidebar)
    <flux:sidebar.brand :name="$brandName" {{ $attributes }}  style="width:210px;">
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-neutral-800 text-white dark:bg-white dark:text-neutral-900 font-bold text-xs">
            {{ $initials }}
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-neutral-800 text-white dark:bg-white dark:text-neutral-900 font-bold text-xs">
            {{ $initials }}
        </x-slot>
    </flux:brand>
@endif
