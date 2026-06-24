<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductService extends Model
{
    protected $table = 'products_services';

    protected $fillable = [
        'business_id',
        'type',
        'name',
        'sku',
        'buying_price',
        'description',
        'selling_price',
        'quantity',
        'low_stock_threshold',
        'unit',
        'image',
        'show_in_store',
        'store_description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'buying_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'quantity' => 'decimal:2',
            'low_stock_threshold' => 'decimal:2',
            'show_in_store' => 'boolean',
        ];
    }

    public function isLowStock(): bool
    {
        return $this->quantity !== null && $this->quantity <= $this->low_stock_threshold;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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
