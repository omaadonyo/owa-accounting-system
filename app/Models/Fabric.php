<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fabric extends Model
{
    protected $fillable = [
        'business_id',
        'roll_code',
        'name',
        'color',
        'supplier',
        'date_received',
        'claimed_meters',
        'verified_meters',
        'used_meters',
        'low_stock_threshold',
        'buying_price',
        'selling_price_per_meter',
        'width',
        'image',
        'show_in_store',
        'store_description',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['remaining_meters'];

    protected function casts(): array
    {
        return [
            'date_received' => 'date',
            'claimed_meters' => 'decimal:2',
            'verified_meters' => 'decimal:2',
            'used_meters' => 'decimal:2',
            'low_stock_threshold' => 'decimal:2',
            'buying_price' => 'decimal:2',
            'selling_price_per_meter' => 'decimal:2',
            'show_in_store' => 'boolean',
        ];
    }

    public function isLowStock(): bool
    {
        $remaining = $this->remaining_meters;
        return $remaining !== null && $remaining <= $this->low_stock_threshold;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function getRemainingMetersAttribute(): ?float
    {
        if ($this->verified_meters === null) {
            return null;
        }

        return max(0, (float) $this->verified_meters - (float) $this->used_meters);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
