<?php

namespace App\Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $table = 'shop_purchases';
    protected $fillable = [
        'user_id',
        'product_id',
        'duration_id',
        'server_id',
        'transaction_id',
        'price',
        'original_price',
        'discount_amount',
        'discount_code',
        'driver_type',
        'status',
        'driver_data',
        'activated_at',
        'expires_at',
        'is_forever',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'driver_data' => 'array',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_forever' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    public function duration(): BelongsTo
    {
        return $this->belongsTo(ProductDuration::class);
    }
    
    public function server(): BelongsTo
    {
        return $this->belongsTo(config('shop.server_model', \App\Models\Server::class));
    }
    
    public function getFormattedPriceAttribute(): string
    {
        $currency = config('shop.currency', 'FC');
        $position = config('shop.currency_position', 'after');
        
        if ($position === 'after') {
            return $this->price . ' ' . $currency;
        }
        
        return $currency . ' ' . $this->price;
    }
    
    public function getIsExpiredAttribute(): bool
    {
        if ($this->is_forever || !$this->expires_at) {
            return false;
        }
        
        return $this->expires_at->isPast();
    }
    
    public function getRemainingTimeAttribute(): ?string
    {
        if ($this->is_forever || !$this->expires_at) {
            return __('shop.duration.forever');
        }
        
        if ($this->is_expired) {
            return __('shop.expired');
        }
        
        $diff = $this->expires_at->diff(now());
        
        $parts = [];
        
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' ' . trans_choice('shop.duration.month', $diff->m);
        }
        
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' ' . trans_choice('shop.duration.day', $diff->d);
        }
        
        if ($diff->h > 0) {
            $parts[] = $diff->h . ' ' . trans_choice('shop.duration.hour', $diff->h);
        }
        
        if ($diff->i > 0) {
            $parts[] = $diff->i . ' ' . trans_choice('shop.duration.minute', $diff->i);
        }
        
        if (empty($parts)) {
            return $diff->s . ' ' . trans_choice('shop.duration.second', $diff->s);
        }
        
        return implode(', ', $parts);
    }
    
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->original_price > 0 && $this->price < $this->original_price) {
            return round((1 - $this->price / $this->original_price) * 100, 2);
        }
        
        return null;
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
    
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}