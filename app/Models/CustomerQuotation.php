<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQuotation extends Model
{
    protected $fillable = [
        'item_id',
        'item_type',
        'business_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_message',
        'length_meters',
        'width_meters',
        'total_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'length_meters' => 'decimal:2',
            'width_meters' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function item(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
