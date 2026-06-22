<?php

use App\Models\Business;
use App\Models\Subscription;
use Illuminate\Support\Facades\Session;

if (! function_exists('currencySymbol')) {
    function currencySymbol(): string
    {
        $code = currentBusiness()?->currency ?? 'UGX';
        return config("currencies.{$code}.symbol", $code . ' ');
    }
}

if (! function_exists('formatCurrency')) {
    function formatCurrency(float $amount, ?int $decimals = null): string
    {
        $code = currentBusiness()?->currency ?? 'UGX';
        $currency = config("currencies.{$code}");
        $dec = $decimals ?? ($currency['decimals'] ?? 2);
        $symbol = $currency['symbol'] ?? $code . ' ';
        return $symbol . number_format($amount, $dec);
    }
}

if (! function_exists('currentBusiness')) {
    function currentBusiness(): ?Business
    {
        $user = auth()->user();
        if (! $user) return null;

        $id = Session::get('active_business_id', $user->business_id);
        return $id ? Business::find($id) : null;
    }
}

if (! function_exists('currentBusinessId')) {
    function currentBusinessId(): ?int
    {
        $user = auth()->user();
        if (! $user) return null;

        return Session::get('active_business_id', $user->business_id);
    }
}

if (! function_exists('canAddBusiness')) {
    function canAddBusiness(): bool
    {
        $user = auth()->user();
        if (! $user) return false;

        $existingCount = $user->businesses()->count();

        $sub = $user->subscription;
        $limit = $sub?->plan?->businesses_limit ?? 2;

        return $limit === -1 || $existingCount < $limit;
    }
}
