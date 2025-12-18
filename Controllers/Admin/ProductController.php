<?php

namespace App\Modules\Shop\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Shop\Models\Product;
use App\Modules\Shop\Models\Category;
use App\Modules\Shop\Models\ProductDuration;
use App\Modules\Shop\Models\ProductDescription;
use App\Modules\Shop\Models\ProductImage;
use App\Modules\Shop\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'durations', 'discounts'])
            ->orderBy('priority')
            ->paginate(20);
            
        $categories = Category::active()->get();
        
        return view('shop::admin.products.index', compact('products', 'categories'));
    }
    
    public function create()
    {
        $categories = Category::active()->get();
        $servers = $this->getAvailableServers();
        
        return view('shop::admin.products.create', compact('categories', 'servers'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:shop_categories,id',
            'driver_type' => 'required|in:vip,rcon,sourcebans,adminsystem,other',
            'vip_group' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'active' => 'boolean',
            'apply_discount_forever' => 'boolean',
            'server_mode' => 'required|in:all,specific',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*.text' => 'required|string',
            'features.*.checked' => 'boolean',
            'durations' => 'nullable|array',
            'durations.*.name' => 'required|string',
            'durations.*.price' => 'required|numeric|min:0',
            'durations.*.is_forever' => 'boolean',
            'durations.*.duration_minutes' => 'nullable|integer|min:0',
            'durations.*.duration_hours' => 'nullable|integer|min:0',
            'durations.*.duration_days' => 'nullable|integer|min:0',
            'durations.*.duration_months' => 'nullable|integer|min:0',
            'servers' => 'nullable|array',
            'servers.*' => 'exists:servers,id',
            'image' => 'nullable|image|max:2048',
        ]);
        
        return DB::transaction(function () use ($request, $validated) {
            // Создание продукта
            $product = Product::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'category_id' => $validated['category_id'],
                'driver_type' => $validated['driver_type'],
                'vip_group' => $validated['vip_group'] ?? null,
                'price' => $validated['price'],
                'original_price' => $validated['original_price'] ?? null,
                'active' => $validated['active'] ?? true,
                'apply_discount_forever' => $validated['apply_discount_forever'] ?? false,
                'server_mode' => $validated['server_mode'],
                'description' => $validated['description'] ?? null,
                'features' => $validated['features'] ?? null,
            ]);
            
            // Загрузка основного изображения
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('shop/products', 'public');
                $product->update(['image' => $path]);
            }
            
            // Добавление длительностей
            if (!empty($validated['durations'])) {
                foreach ($validated['durations'] as $durationData) {
                    $product->durations()->create([
                        'name' => $durationData['name'],
                        'price' => $durationData['price'],
                        'original_price' => $durationData['original_price'] ?? null,
                        'is_forever' => $durationData['is_forever'] ?? false,
                        'duration_minutes' => $durationData['duration_minutes'] ?? null,
                        'duration_hours' => $durationData['duration_hours'] ?? null,
                        'duration_days' => $durationData['duration_days'] ?? null,
                        'duration_months' => $durationData['duration_months'] ?? null,
                    ]);
                }
            }
            
            // Привязка серверов
            if ($validated['server_mode'] === 'specific' && !empty($validated['servers'])) {
                $product->servers()->sync($validated['servers']);
            }
            
            // Загрузка дополнительных изображений
            if ($request->has('image_ids')) {
                ProductImage::whereIn('id', $request->image_ids)
                    ->update(['product_id' => $product->id]);
            }
            
            return redirect()
                ->route('shop.admin.products.index')
                ->with('success', 'Товар успешно создан');
        });
    }
    
    public function edit($id)
    {
        $product = Product::with(['durations', 'descriptions', 'images', 'servers'])
            ->findOrFail($id);
            
        $categories = Category::active()->get();
        $servers = $this->getAvailableServers();
        
        return view('shop::admin.products.edit', compact('product', 'categories', 'servers'));
    }
    
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:shop_categories,id',
            'driver_type' => 'required|in:vip,rcon,sourcebans,adminsystem,other',
            'vip_group' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'active' => 'boolean',
            'apply_discount_forever' => 'boolean',
            'server_mode' => 'required|in:all,specific',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*.text' => 'required|string',
            'features.*.checked' => 'boolean',
            'durations' => 'nullable|array',
            'durations.*.id' => 'nullable|exists:shop_product_durations,id',
            'durations.*.name' => 'required|string',
            'durations.*.price' => 'required|numeric|min:0',
            'durations.*.is_forever' => 'boolean',
            'durations.*.duration_minutes' => 'nullable|integer|min:0',
            'durations.*.duration_hours' => 'nullable|integer|min:0',
            'durations.*.duration_days' => 'nullable|integer|min:0',
            'durations.*.duration_months' => 'nullable|integer|min:0',
            'servers' => 'nullable|array',
            'servers.*' => 'exists:servers,id',
            'image' => 'nullable|image|max:2048',
        ]);
        
        return DB::transaction(function () use ($request, $product, $validated) {
            // Обновление продукта
            $product->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'category_id' => $validated['category_id'],
                'driver_type' => $validated['driver_type'],
                'vip_group' => $validated['vip_group'] ?? null,
                'price' => $validated['price'],
                'original_price' => $validated['original_price'] ?? null,
                'active' => $validated['active'] ?? true,
                'apply_discount_forever' => $validated['apply_discount_forever'] ?? false,
                'server_mode' => $validated['server_mode'],
                'description' => $validated['description'] ?? null,
                'features' => $validated['features'] ?? null,
            ]);
            
            // Обновление изображения
            if ($request->hasFile('image')) {
                // Удаляем старое изображение
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                
                $path = $request->file('image')->store('shop/products', 'public');
                $product->update(['image' => $path]);
            }
            
            // Удаляем отмеченные для удаления изображения
            if ($request->has('delete_images')) {
                foreach ($request->delete_images as $imageId) {
                    $image = ProductImage::find($imageId);
                    if ($image) {
                        Storage::disk('public')->delete($image->path);
                        $image->delete();
                    }
                }
            }
            
            // Обновление длительностей
            if (!empty($validated['durations'])) {
                $existingIds = [];
                
                foreach ($validated['durations'] as $durationData) {
                    if (isset($durationData['id'])) {
                        // Обновляем существующую длительность
                        $duration = ProductDuration::find($durationData['id']);
                        if ($duration && $duration->product_id == $product->id) {
                            $duration->update([
                                'name' => $durationData['name'],
                                'price' => $durationData['price'],
                                'original_price' => $durationData['original_price'] ?? null,
                                'is_forever' => $durationData['is_forever'] ?? false,
                                'duration_minutes' => $durationData['duration_minutes'] ?? null,
                                'duration_hours' => $durationData['duration_hours'] ?? null,
                                'duration_days' => $durationData['duration_days'] ?? null,
                                'duration_months' => $durationData['duration_months'] ?? null,
                            ]);
                            $existingIds[] = $duration->id;
                        }
                    } else {
                        // Создаем новую длительность
                        $duration = $product->durations()->create([
                            'name' => $durationData['name'],
                            'price' => $durationData['price'],
                            'original_price' => $durationData['original_price'] ?? null,
                            'is_forever' => $durationData['is_forever'] ?? false,
                            'duration_minutes' => $durationData['duration_minutes'] ?? null,
                            'duration_hours' => $durationData['duration_hours'] ?? null,
                            'duration_days' => $durationData['duration_days'] ?? null,
                            'duration_months' => $durationData['duration_months'] ?? null,
                        ]);
                        $existingIds[] = $duration->id;
                    }
                }
                
                // Удаляем удаленные длительности
                $product->durations()->whereNotIn('id', $existingIds)->delete();
            }
            
            // Обновление серверов
            if ($validated['server_mode'] === 'specific' && !empty($validated['servers'])) {
                $product->servers()->sync($validated['servers']);
            } else {
                $product->servers()->detach();
            }
            
            return redirect()
                ->route('shop.admin.products.index')
                ->with('success', 'Товар успешно обновлен');
        });
    }
    
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        DB::transaction(function () use ($product) {
            // Удаляем изображения
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->path);
            }
            
            $product->delete();
        });
        
        return redirect()
            ->route('shop.admin.products.index')
            ->with('success', 'Товар успешно удален');
    }
    
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
            'product_id' => 'nullable|exists:shop_products,id',
        ]);
        
        $path = $request->file('image')->store('shop/products', 'public');
        
        $image = ProductImage::create([
            'product_id' => $request->product_id,
            'path' => $path,
            'filename' => $request->file('image')->hashName(),
            'original_name' => $request->file('image')->getClientOriginalName(),
            'mime_type' => $request->file('image')->getMimeType(),
            'size' => $request->file('image')->getSize(),
        ]);
        
        // Создаем миниатюру
        $this->createThumbnail($path);
        
        return response()->json([
            'success' => true,
            'image' => [
                'id' => $image->id,
                'url' => $image->url,
                'thumbnail_url' => $image->thumbnail_url,
            ]
        ]);
    }
    
    public function deleteImage($id)
    {
        $image = ProductImage::findOrFail($id);
        
        Storage::disk('public')->delete($image->path);
        
        // Удаляем миниатюру
        $pathinfo = pathinfo($image->path);
        $thumbnail = $pathinfo['dirname'] . '/thumbs/' . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];
        Storage::disk('public')->delete($thumbnail);
        
        $image->delete();
        
        return response()->json(['success' => true]);
    }
    
    private function createThumbnail($path)
    {
        $pathinfo = pathinfo($path);
        $fullPath = storage_path('app/public/' . $path);
        $thumbPath = storage_path('app/public/' . $pathinfo['dirname'] . '/thumbs');
        
        if (!file_exists($thumbPath)) {
            mkdir($thumbPath, 0755, true);
        }
        
        $thumbFile = $thumbPath . '/' . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];
        
        // Используем GD для создания миниатюры
        if (extension_loaded('gd')) {
            $image = null;
            
            switch ($pathinfo['extension']) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($fullPath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($fullPath);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($fullPath);
                    break;
            }
            
            if ($image) {
                $width = imagesx($image);
                $height = imagesy($image);
                
                $newWidth = 200;
                $newHeight = (int)($height * ($newWidth / $width));
                
                $thumb = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                switch ($pathinfo['extension']) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($thumb, $thumbFile, 90);
                        break;
                    case 'png':
                        imagepng($thumb, $thumbFile, 9);
                        break;
                    case 'gif':
                        imagegif($thumb, $thumbFile);
                        break;
                }
                
                imagedestroy($image);
                imagedestroy($thumb);
            }
        }
    }
    
    private function getAvailableServers()
    {
        if (class_exists('\App\Models\Server')) {
            return \App\Models\Server::all();
        }
        
        return collect();
    }
}