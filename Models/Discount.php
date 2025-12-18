<?php

namespace App\Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Discount extends Model
{
    protected $table = 'shop_discounts';
    protected $fillable = [
        'name',
        'type',
        'value',
        'category_id',
        'product_id',
        'start_date',
        'end_date',
        'is_active',
        'priority',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'shop_product_discount');
    }
    
    public function getIsActiveAttribute(): bool
    {
        if (!$this->attributes['is_active']) {
            return false;
        }
        
        $now = now();
        
        if ($this->start_date && $this->start_date->gt($now)) {
            return false;
        }
        
        if ($this->end_date && $this->end_date->lt($now)) {
            return false;
        }
        
        return true;
    }
    
    public function applyDiscount(float $price): float
    {
        if (!$this->is_active) {
            return $price;
        }
        
        if ($this->type === 'percentage') {
            return $price * (1 - $this->value / 100);
        }
        
        if ($this->type === 'fixed') {
            return max(0, $price - $this->value);
        }
        
        return $price;
    }
    
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        }
        
        $currency = config('shop.currency', 'FC');
        return $this->value . ' ' . $currency;
    }
    
    public function scopeActive($query)
    {
        $now = now();
        
        return $query->where('is_active', true)
            ->where(function($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
            });
    }
}