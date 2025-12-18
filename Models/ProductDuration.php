<?php

namespace App\Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductDuration extends Model
{
    use SoftDeletes;
    
    protected $table = 'shop_product_durations';
    protected $fillable = [
        'product_id',
        'name',
        'duration_minutes',
        'duration_hours',
        'duration_days',
        'duration_months',
        'is_forever',
        'price',
        'original_price',
        'priority',
    ];
    
    protected $casts = [
        'is_forever' => 'boolean',
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
    ];
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
    
    protected function totalSeconds(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_forever) {
                    return null;
                }
                
                $seconds = 0;
                $seconds += ($this->duration_minutes ?? 0) * 60;
                $seconds += ($this->duration_hours ?? 0) * 3600;
                $seconds += ($this->duration_days ?? 0) * 86400;
                $seconds += ($this->duration_months ?? 0) * 2592000; // 30 дней в месяце
                
                return $seconds;
            }
        );
    }
    
    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_forever) {
                    return __('shop.duration.forever');
                }
                
                $parts = [];
                
                if ($this->duration_months) {
                    $parts[] = $this->duration_months . ' ' . trans_choice('shop.duration.month', $this->duration_months);
                }
                
                if ($this->duration_days) {
                    $parts[] = $this->duration_days . ' ' . trans_choice('shop.duration.day', $this->duration_days);
                }
                
                if ($this->duration_hours) {
                    $parts[] = $this->duration_hours . ' ' . trans_choice('shop.duration.hour', $this->duration_hours);
                }
                
                if ($this->duration_minutes) {
                    $parts[] = $this->duration_minutes . ' ' . trans_choice('shop.duration.minute', $this->duration_minutes);
                }
                
                return implode(', ', $parts);
            }
        );
    }
    
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->original_price > 0 && $this->price < $this->original_price) {
            return round((1 - $this->price / $this->original_price) * 100, 2);
        }
        
        return null;
    }
    
    public function getDisplayPriceAttribute(): string
    {
        $currency = config('shop.currency', 'FC');
        $position = config('shop.currency_position', 'after');
        
        if ($position === 'after') {
            return $this->price . ' ' . $currency;
        }
        
        return $currency . ' ' . $this->price;
    }
}