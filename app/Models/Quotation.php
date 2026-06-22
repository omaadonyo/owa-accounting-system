<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'business_id',
        'customer_id',
        'quotation_number',
        'issue_date',
        'valid_until',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_name',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'status',
        'created_by',
        'updated_by',
        'show_discount_column',
        'hide_total',
        'custom_title',
        'show_amount_in_words',
        'act_as_delivery_note',
        'tax_inclusive',
        'wht_rate',
        'wht_amount',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'show_discount_column' => 'boolean',
            'hide_total' => 'boolean',
            'show_amount_in_words' => 'boolean',
            'act_as_delivery_note' => 'boolean',
            'tax_inclusive' => 'boolean',
            'wht_rate' => 'decimal:2',
            'wht_amount' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function invoice(): HasMany
    {
        return $this->hasMany(Invoice::class, 'quotation_id');
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
