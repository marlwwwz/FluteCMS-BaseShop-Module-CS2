<?php

namespace App\Modules\Shop\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Shop\Models\Product;
use App\Modules\Shop\Models\Category;
use App\Modules\Shop\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShopApiController extends Controller
{
    /**
     * Получить товары с фильтрацией
     */
    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'discounts' => function($q) {
            $q->active();
        }]);
        
        // Фильтрация по категории
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }
        
        // Фильтрация по типу драйвера
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('driver_type', $request->type);
        }
        
        // Поиск
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        // Сортировка
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        switch ($sortBy) {
            case 'price_asc':
                $query->orderByRaw('CASE WHEN original_price IS NOT NULL THEN original_price ELSE price END ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('CASE WHEN original_price IS NOT NULL THEN original_price ELSE price END DESC');
                break;
            case 'popular':
                $query->withCount('purchases')->orderBy('purchases_count', 'desc');
                break;
            default:
                $query->orderBy('priority')->orderBy('created_at', $sortOrder);
        }
        
        $products = $query->active()->paginate($request->get('per_page', 12));
        
        return response()->json([
            'success' => true,
            'data' => $products,
            'html' => view('shop::components.products-grid', compact('products'))->render()
        ]);
    }
    
    /**
     * Получить информацию о товаре
     */
    public function getProduct($id): JsonResponse
    {
        $product = Product::with([
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
                $q->active();
            },
            'servers'
        ])->active()->find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не найден'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }
    
    /**
     * Получить историю покупок пользователя
     */
    public function getUserPurchases(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $purchases = Purchase::with(['product', 'server', 'duration'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));
        
        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }
    
    /**
     * Проверить доступность сервера для товара
     */
    public function checkProductAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'server_id' => 'required|exists:servers,id'
        ]);
        
        $product = Product::find($request->product_id);
        
        if ($product->server_mode === 'all') {
            $available = true;
        } else {
            $available = $product->servers()->where('server_id', $request->server_id)->exists();
        }
        
        return response()->json([
            'success' => true,
            'available' => $available,
            'message' => $available ? 'Товар доступен' : 'Товар недоступен на выбранном сервере'
        ]);
    }
    
    /**
     * Получить текущие скидки
     */
    public function getActiveDiscounts(): JsonResponse
    {
        $discounts = \App\Modules\Shop\Models\Discount::active()
            ->with(['category', 'product'])
            ->orderBy('priority')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $discounts
        ]);
    }
    
    /**
     * Получить статистику магазина
     */
    public function getShopStats(): JsonResponse
    {
        $totalProducts = Product::active()->count();
        $totalCategories = Category::active()->count();
        $activeDiscounts = \App\Modules\Shop\Models\Discount::active()->count();
        
        $today = now()->startOfDay();
        $purchasesToday = Purchase::where('created_at', '>=', $today)->count();
        $revenueToday = Purchase::where('created_at', '>=', $today)->sum('price');
        
        $month = now()->startOfMonth();
        $purchasesMonth = Purchase::where('created_at', '>=', $month)->count();
        $revenueMonth = Purchase::where('created_at', '>=', $month)->sum('price');
        
        return response()->json([
            'success' => true,
            'data' => [
                'products' => $totalProducts,
                'categories' => $totalCategories,
                'discounts' => $activeDiscounts,
                'today' => [
                    'purchases' => $purchasesToday,
                    'revenue' => $revenueToday
                ],
                'month' => [
                    'purchases' => $purchasesMonth,
                    'revenue' => $revenueMonth
                ]
            ]
        ]);
    }
    
    /**
     * Обновить приоритет товара
     */
    public function updateProductPriority(Request $request, $id): JsonResponse
    {
        $request->validate([
            'priority' => 'required|integer|min:0'
        ]);
        
        $product = Product::findOrFail($id);
        $product->update(['priority' => $request->priority]);
        
        return response()->json([
            'success' => true,
            'message' => 'Приоритет обновлен'
        ]);
    }
    
    /**
     * Быстрое изменение статуса товара
     */
    public function toggleProductStatus($id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update(['active' => !$product->active]);
        
        return response()->json([
            'success' => true,
            'active' => $product->active,
            'message' => 'Статус обновлен'
        ]);
    }
    
    /**
     * Получить категории с товарами
     */
    public function getCategoriesWithProducts(): JsonResponse
    {
        $categories = Category::with(['products' => function($query) {
            $query->active()->orderBy('priority');
        }])->active()->orderBy('priority')->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}