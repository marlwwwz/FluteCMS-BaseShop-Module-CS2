<?php

namespace App\Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use SoftDeletes;
    
    protected $table = 'shop_products';
    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'driver_type',
        'vip_group',
        'price',
        'original_price',
        'active',
        'apply_discount_forever',
        'image',
        'server_mode',
        'features',
        'description',
        'priority',
    ];
    
    protected $casts = [
        'active' => 'boolean',
        'apply_discount_forever' => 'boolean',
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'features' => 'array',
    ];
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function durations(): HasMany
    {
        return $this->hasMany(ProductDuration::class)->orderBy('priority');
    }
    
    public function descriptions(): HasMany
    {
        return $this->hasMany(ProductDescription::class)->orderBy('priority');
    }
    
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('priority');
    }
    
    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'shop_product_discount')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('priority');
    }
    
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
    
    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(
            config('shop.server_model', \App\Models\Server::class),
            'shop_product_server',
            'product_id',
            'server_id'
        );
    }
    
    public function getMainImageAttribute()
    {
        $mainImage = $this->images()->where('is_main', true)->first();
        return $mainImage ?: $this->images()->first();
    }
    
    public function getCurrentPriceAttribute()
    {
        $discount = $this->getActiveDiscount();
        
        if ($discount) {
            return $this->calculateDiscountedPrice($this->price, $discount);
        }
        
        return $this->price;
    }
    
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->original_price > 0 && $this->price < $this->original_price) {
            return round((1 - $this->price / $this->original_price) * 100, 2);
        }
        
        return null;
    }
    
    public function getFormattedFeaturesAttribute(): array
    {
        if (empty($this->features)) {
            return [];
        }
        
        return array_map(function($feature) {
            return [
                'text' => $feature['text'] ?? '',
                'checked' => $feature['checked'] ?? false,
                'icon' => $feature['icon'] ?? null,
            ];
        }, $this->features);
    }
    
    public function getIsOnDiscountAttribute(): bool
    {
        return $this->original_price > $this->price || $this->discounts()->exists();
    }
    
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    public function scopeWithDiscount($query)
    {
        return $query->whereHas('discounts', function($q) {
            $q->where('is_active', true)
              ->where(function($q) {
                  $q->whereNull('start_date')->orWhere('start_date', '<=', now());
              })
              ->where(function($q) {
                  $q->whereNull('end_date')->orWhere('end_date', '>=', now());
              });
        });
    }
    
    private function getActiveDiscount()
    {
        return $this->discounts->first();
    }
    
    private function calculateDiscountedPrice($price, Discount $discount): float
    {
        if ($discount->type === 'percentage') {
            return $price * (1 - $discount->value / 100);
        }
        
        if ($discount->type === 'fixed') {
            return max(0, $price - $discount->value);
        }
        
        return $price;
    }
}