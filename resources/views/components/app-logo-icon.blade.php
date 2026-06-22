@php
    $name = currentBusiness()?->name ?? config('app.name', 'AK');
    $words = explode(' ', $name);
    $initials = collect($words)->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->implode('');
@endphp

<span {{ $attributes->merge(['class' => 'font-bold']) }}>{{ $initials }}</span>
