<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'business_id',
        'customer_id',
        'invoice_number',
        'issue_date',
        'due_date',
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
        'quotation_id',
        'paid_amount',
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
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
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
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
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
