<?php

use App\Modules\Shop\Models\Product;
use App\Modules\Shop\Models\Category;
use App\Modules\Shop\Models\Discount;
use App\Modules\Shop\Services\ShopService;

if (!function_exists('shop_product')) {
    /**
     * Получить товар по ID
     */
    function shop_product($id)
    {
        return Product::with(['category', 'durations', 'discounts'])
            ->active()
            ->find($id);
    }
}

if (!function_exists('shop_products')) {
    /**
     * Получить товары с фильтрацией
     */
    function shop_products($limit = null, $category = null, $type = null)
    {
        $query = Product::with(['category', 'discounts' => function($q) {
            $q->active();
        }]);
        
        if ($category) {
            $query->whereHas('category', function($q) use ($category) {
                if (is_numeric($category)) {
                    $q->where('id', $category);
                } else {
                    $q->where('slug', $category);
                }
            });
        }
        
        if ($type) {
            $query->where('driver_type', $type);
        }
        
        $query->active()->orderBy('priority');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
}

if (!function_exists('shop_categories')) {
    /**
     * Получить категории
     */
    function shop_categories($withProducts = false)
    {
        $query = Category::query();
        
        if ($withProducts) {
            $query->with(['products' => function($q) {
                $q->active()->orderBy('priority');
            }]);
        }
        
        return $query->active()->orderBy('priority')->get();
    }
}

if (!function_exists('shop_discounts')) {
    /**
     * Получить активные скидки
     */
    function shop_discounts()
    {
        return Discount::active()->orderBy('priority')->get();
    }
}

if (!function_exists('shop_calculate_price')) {
    /**
     * Рассчитать цену с учетом скидок
     */
    function shop_calculate_price($price, $discounts = null)
    {
        if (!$discounts) {
            return $price;
        }
        
        $finalPrice = $price;
        
        foreach ($discounts as $discount) {
            if ($discount->is_active) {
                $finalPrice = $discount->applyDiscount($finalPrice);
            }
        }
        
        return max(0, $finalPrice);
    }
}

if (!function_exists('shop_format_price')) {
    /**
     * Форматировать цену
     */
    function shop_format_price($price)
    {
        $currency = config('shop.currency', 'FC');
        $position = config('shop.currency_position', 'after');
        $decimalSeparator = config('shop.decimal_separator', '.');
        $thousandSeparator = config('shop.thousand_separator', ' ');
        
        $formatted = number_format($price, 2, $decimalSeparator, $thousandSeparator);
        
        if ($position === 'after') {
            return $formatted . ' ' . $currency;
        }
        
        return $currency . ' ' . $formatted;
    }
}

if (!function_exists('shop_user_purchases')) {
    /**
     * Получить покупки пользователя
     */
    function shop_user_purchases($userId = null, $limit = null)
    {
        if (!$userId && auth()->check()) {
            $userId = auth()->id();
        }
        
        if (!$userId) {
            return collect();
        }
        
        $query = \App\Modules\Shop\Models\Purchase::with(['product', 'server', 'duration'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
}

if (!function_exists('shop_purchase_stats')) {
    /**
     * Получить статистику покупок
     */
    function shop_purchase_stats($period = 'month')
    {
        $shopService = app(ShopService::class);
        return $shopService->getPurchaseStats($period);
    }
}

if (!function_exists('shop_is_product_available')) {
    /**
     * Проверить доступность товара для сервера
     */
    function shop_is_product_available($productId, $serverId)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return false;
        }
        
        if ($product->server_mode === 'all') {
            return true;
        }
        
        return $product->servers()->where('server_id', $serverId)->exists();
    }
}

if (!function_exists('shop_buy_button')) {
    /**
     * Сгенерировать кнопку покупки
     */
    function shop_buy_button($product, $attributes = [])
    {
        $defaultAttributes = [
            'class' => 'btn-buy',
            'data-product-id' => $product->id,
            'data-product-name' => $product->name,
        ];
        
        $attributes = array_merge($defaultAttributes, $attributes);
        
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= ' ' . $key . '="' . e($value) . '"';
        }
        
        $price = shop_format_price($product->current_price);
        
        return '<button' . $attrString . '>
            <i class="ph-shopping-cart"></i>
            Купить за ' . $price . '
        </button>';
    }
}