<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'quotations_limit',
        'invoices_limit',
        'receipts_limit',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'quotations_limit' => 'integer',
            'invoices_limit' => 'integer',
            'receipts_limit' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isUnlimited(string $feature): bool
    {
        return $this->{$feature . '_limit'} === -1;
    }

    public function limit(string $feature): int
    {
        return $this->{$feature . '_limit'};
    }

    public function isFree(): bool
    {
        return $this->slug === 'free';
    }

    public function getPrice(string $billingCycle): float
    {
        return $billingCycle === 'yearly' ? $this->price_yearly : $this->price_monthly;
    }
}
