<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'email',
        'address',
        'logo',
        'invoice_notes',
        'quotes_notes',
        'receipt_notes',
        'accent_color',
        'invoice_template',
        'currency',
        'store_active',
        'store_font',
        'store_primary_color',
        'store_accent_color',
        'store_headline',
        'store_subheadline',
        'store_about_text',
        'store_hero_image',
        'store_show_products',
        'store_show_about',
        'store_show_contact',
        'store_contact_email',
        'store_contact_phone',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'store_active' => 'boolean',
            'store_show_products' => 'boolean',
            'store_show_about' => 'boolean',
            'store_show_contact' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function fabrics(): HasMany
    {
        return $this->hasMany(Fabric::class);
    }

    public function productsServices(): HasMany
    {
        return $this->hasMany(ProductService::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->withPivot('role')
            ->withTimestamps();
    }
}
