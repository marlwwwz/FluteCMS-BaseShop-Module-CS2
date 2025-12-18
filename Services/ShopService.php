<?php

namespace App\Modules\Shop\Services;

use App\Modules\Shop\Models\Product;
use App\Modules\Shop\Models\Purchase;
use App\Modules\Shop\Models\Discount;
use App\Modules\Shop\Events\ProductPurchased;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ShopService
{
    public function getAvailableProducts($userId = null)
    {
        return Product::with(['category', 'durations', 'discounts', 'images'])
            ->active()
            ->orderBy('priority')
            ->get()
            ->map(function($product) use ($userId) {
                // Проверяем доступность для пользователя
                $product->is_available = $this->checkProductAvailability($product, $userId);
                return $product;
            });
    }
    
    public function getProductWithDetails($id)
    {
        return Product::with([
            'category',
            'durations' => function($q) {
                $q->orderBy('priority');
            },
            'descriptions' => function($q) {
                $q->orderBy('priority');
            },
            'images' => function($q) {
                $q->orderBy('priority');
            },
            'discounts' => function($q) {
                $q->active()->orderBy('priority');
            },
            'servers'
        ])->active()->findOrFail($id);
    }
    
    public function calculatePrice(Product $product, $durationId = null)
    {
        $price = $product->price;
        
        // Если указана длительность, используем её цену
        if ($durationId) {
            $duration = $product->durations()->find($durationId);
            if ($duration) {
                $price = $duration->price;
            }
        }
        
        // Применяем скидки
        foreach ($product->discounts as $discount) {
            if ($discount->is_active) {
                $price = $discount->applyDiscount($price);
            }
        }
        
        // Применяем скидку на категорию
        if ($product->category) {
            $categoryDiscount = Discount::where('category_id', $product->category_id)
                ->active()
                ->first();
                
            if ($categoryDiscount) {
                $price = $categoryDiscount->applyDiscount($price);
            }
        }
        
        return max(0, $price);
    }
    
    public function processPurchase(User $user, $productId, $serverId, $durationId)
    {
        return DB::transaction(function () use ($user, $productId, $serverId, $durationId) {
            try {
                // Получаем данные
                $product = Product::with(['durations' => function($query) use ($durationId) {
                    $query->where('id', $durationId);
                }])->active()->findOrFail($productId);
                
                $duration = $product->durations->first();
                
                if (!$duration) {
                    throw new Exception('Выбранная длительность недоступна');
                }
                
                $server = $this->getServer($serverId);
                if (!$server) {
                    throw new Exception('Сервер не найден');
                }
                
                // Проверяем доступность товара на сервере
                if (!$this->checkProductForServer($product, $serverId)) {
                    throw new Exception('Товар недоступен на выбранном сервере');
                }
                
                // Рассчитываем цену
                $originalPrice = $duration->price;
                $finalPrice = $this->calculatePrice($product, $durationId);
                $discountAmount = $originalPrice - $finalPrice;
                
                // Проверяем баланс пользователя
                if ($user->balance < $finalPrice) {
                    throw new Exception('Недостаточно средств на балансе');
                }
                
                // Создаем запись о покупке
                $purchase = Purchase::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'duration_id' => $duration->id,
                    'server_id' => $server->id,
                    'transaction_id' => $this->generateTransactionId(),
                    'price' => $finalPrice,
                    'original_price' => $originalPrice,
                    'discount_amount' => $discountAmount,
                    'driver_type' => $product->driver_type,
                    'status' => 'pending',
                ]);
                
                // Списание средств
                $user->decrement('balance', $finalPrice);
                
                // Применяем товар на сервере
                $this->applyProduct($product, $user, $server, $duration, $purchase);
                
                // Обновляем статус покупки
                $purchase->update([
                    'status' => 'completed',
                    'activated_at' => now(),
                    'expires_at' => $duration->is_forever ? null : now()->addSeconds($duration->total_seconds),
                    'is_forever' => $duration->is_forever,
                ]);
                
                // Отправляем событие
                event(new ProductPurchased($purchase, $user));
                
                // Логируем успешную покупку
                Log::info('Purchase completed', [
                    'purchase_id' => $purchase->id,
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'price' => $finalPrice,
                ]);
                
                return $purchase;
                
            } catch (Exception $e) {
                Log::error('Purchase processing failed', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
                
                throw $e;
            }
        });
    }
    
    private function applyProduct(Product $product, User $user, $server, $duration, Purchase $purchase)
    {
        $driver = $this->getDriver($product->driver_type);
        
        if (!$driver) {
            throw new Exception('Драйвер для применения товара не найден');
        }
        
        $data = [
            'product' => $product,
            'user' => $user,
            'server' => $server,
            'duration' => $duration,
            'purchase' => $purchase,
        ];
        
        return $driver->apply($data);
    }
    
    private function getDriver($type)
    {
        $drivers = config('shop.drivers', []);
        
        if (isset($drivers[$type]) && class_exists($drivers[$type])) {
            return app($drivers[$type]);
        }
        
        return null;
    }
    
    private function checkProductAvailability(Product $product, $userId = null)
    {
        // Проверяем активность
        if (!$product->active) {
            return false;
        }
        
        // Проверяем срок действия скидок
        if ($product->apply_discount_forever) {
            // Проверяем наличие активных скидок
            $hasActiveDiscounts = $product->discounts()
                ->active()
                ->exists();
                
            if (!$hasActiveDiscounts) {
                return false;
            }
        }
        
        return true;
    }
    
    private function checkProductForServer(Product $product, $serverId)
    {
        if ($product->server_mode === 'all') {
            return true;
        }
        
        return $product->servers()->where('server_id', $serverId)->exists();
    }
    
    private function getServer($serverId)
    {
        $serverModel = config('shop.server_model', \App\Models\Server::class);
        
        if (class_exists($serverModel)) {
            return $serverModel::find($serverId);
        }
        
        return null;
    }
    
    private function generateTransactionId()
    {
        return 'TXN-' . strtoupper(Str::random(8)) . '-' . time();
    }
    
    public function getUserPurchases($userId, $limit = null)
    {
        $query = Purchase::with(['product', 'server', 'duration'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
    
    public function getPurchaseStats($period = 'month')
    {
        $now = now();
        
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                break;
            default:
                $startDate = $now->copy()->subMonth();
        }
        
        return [
            'total' => Purchase::where('created_at', '>=', $startDate)->count(),
            'revenue' => Purchase::where('created_at', '>=', $startDate)->sum('price'),
            'average' => Purchase::where('created_at', '>=', $startDate)->avg('price'),
            'completed' => Purchase::where('created_at', '>=', $startDate)
                ->where('status', 'completed')
                ->count(),
        ];
    }
}