<?php

namespace App\Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $table = 'shop_product_images';
    protected $fillable = [
        'product_id',
        'path',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'priority',
        'is_main',
    ];
    
    protected $casts = [
        'is_main' => 'boolean',
    ];
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
    
    public function getThumbnailUrlAttribute(): string
    {
        $pathinfo = pathinfo($this->path);
        $thumbnail = $pathinfo['dirname'] . '/thumbs/' . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];
        
        if (file_exists(storage_path('app/public/' . $thumbnail))) {
            return asset('storage/' . $thumbnail);
        }
        
        return $this->url;
    }
}