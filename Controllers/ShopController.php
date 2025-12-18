<?php

namespace App\Modules\Shop\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shop\Models\Product;
use App\Modules\Shop\Models\Category;
use App\Modules\Shop\Services\ShopService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    protected $shopService;
    
    public function __construct(ShopService $shopService)
    {
        $this->shopService = $shopService;
    }
    
    public function index()
    {
        $categories = Category::with(['products' => function($query) {
            $query->where('active', true)
                  ->with(['durations', 'discounts']);
        }])->get();
        
        return view('shop::index', compact('categories'));
    }
    
    public function show($id)
    {
        $product = Product::with(['durations', 'descriptions', 'images', 'discounts'])
            ->where('active', true)
            ->findOrFail($id);
            
        $servers = $this->shopService->getAvailableServers();
        
        return view('shop::product', compact('product', 'servers'));
    }
    
    public function confirmPurchase(Request $request, $id)
    {
        $request->validate([
            'server_id' => 'required|integer',
            'duration_id' => 'required|integer',
        ]);
        
        $product = Product::with(['durations' => function($query) use ($request) {
            $query->where('id', $request->duration_id);
        }])->findOrFail($id);
        
        $server = $this->shopService->getServer($request->server_id);
        $duration = $product->durations->first();
        
        if (!$duration) {
            return back()->withErrors(['duration' => 'Выбранная длительность недоступна']);
        }
        
        $price = $this->shopService->calculatePrice($product, $duration);
        
        return view('shop::purchase-confirm', compact('product', 'server', 'duration', 'price'));
    }
    
    public function processPurchase(Request $request, $id)
    {
        $request->validate([
            'server_id' => 'required|integer',
            'duration_id' => 'required|integer',
            'confirm' => 'required|boolean',
        ]);
        
        if (!$request->confirm) {
            return redirect()->route('shop.product', $id);
        }
        
        try {
            $purchase = $this->shopService->processPurchase(
                auth()->user(),
                $id,
                $request->server_id,
                $request->duration_id
            );
            
            return redirect()->route('shop.purchase.success', $purchase->id)
                ->with('success', 'Покупка успешно завершена!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}