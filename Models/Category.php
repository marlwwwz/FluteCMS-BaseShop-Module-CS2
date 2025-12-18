<?php

namespace App\Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'shop_categories';
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'priority',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('priority');
    }
    
    public function getActiveProductsAttribute()
    {
        return $this->products()->active()->get();
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}