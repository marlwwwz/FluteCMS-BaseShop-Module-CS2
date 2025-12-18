<?php

namespace App\Modules\Shop\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Shop\Models\Category;
use App\Modules\Shop\Models\Product;
use App\Modules\Shop\Models\ProductDuration;
use App\Modules\Shop\Models\Discount;

class ShopSeeder extends Seeder
{
    public function run()
    {
        // Создание категорий
        $categories = [
            [
                'name' => 'VIP',
                'slug' => 'vip',
                'icon' => 'ph-crown',
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Админ-доступ',
                'slug' => 'admin-access',
                'icon' => 'ph-shield-check',
                'priority' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Донат',
                'slug' => 'donate',
                'icon' => 'ph-heart',
                'priority' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Скины',
                'slug' => 'skins',
                'icon' => 'ph-palette',
                'priority' => 4,
                'is_active' => true,
            ],
        ];
        
        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }
        
        // Создание VIP товаров
        $vipCategory = Category::where('slug', 'vip')->first();
        
        $vipProducts = [
            [
                'name' => 'VIP Стандарт',
                'slug' => 'vip-standart',
                'category_id' => $vipCategory->id,
                'driver_type' => 'vip',
                'vip_group' => 'vip',
                'price' => 100.00,
                'original_price' => 150.00,
                'active' => true,
                'apply_discount_forever' => false,
                'server_mode' => 'specific',
                'description' => 'Стандартный VIP доступ с базовыми привилегиями',
                'features' => json_encode([
                    ['text' => 'Доступ к VIP чату', 'checked' => true],
                    ['text' => 'Цветной ник в чате', 'checked' => true],
                    ['text' => 'Приоритет на подключение', 'checked' => true],
                    ['text' => 'Доступ к специальным командам', 'checked' => true],
                    ['text' => 'Иммунитет к флешкам', 'checked' => false],
                ]),
                'priority' => 1,
            ],
            [
                'name' => 'VIP Премиум',
                'slug' => 'vip-premium',
                'category_id' => $vipCategory->id,
                'driver_type' => 'vip',
                'vip_group' => 'premium',
                'price' => 250.00,
                'original_price' => 300.00,
                'active' => true,
                'apply_discount_forever' => false,
                'server_mode' => 'specific',
                'description' => 'Премиум VIP доступ с расширенными привилегиями',
                'features' => json_encode([
                    ['text' => 'Все преимущества Стандарт VIP', 'checked' => true],
                    ['text' => 'Иммунитет к флешкам', 'checked' => true],
                    ['text' => 'Дополнительный слот инвентаря', 'checked' => true],
                    ['text' => 'Эксклюзивный доступ к моделям', 'checked' => true],
                    ['text' => 'Приоритет в техподдержке', 'checked' => true],
                ]),
                'priority' => 2,
            ],
        ];
        
        foreach ($vipProducts as $productData) {
            $product = Product::create($productData);
            
            // Добавление длительностей
            $durations = [
                [
                    'name' => '1 месяц',
                    'duration_months' => 1,
                    'is_forever' => false,
                    'price' => $product->price,
                    'original_price' => $product->original_price,
                    'priority' => 1,
                ],
                [
                    'name' => '3 месяца',
                    'duration_months' => 3,
                    'is_forever' => false,
                    'price' => $product->price * 2.5, // Скидка за 3 месяца
                    'original_price' => $product->original_price * 3,
                    'priority' => 2,
                ],
                [
                    'name' => 'Навсегда',
                    'is_forever' => true,
                    'price' => $product->price * 10, // Цена за вечный доступ
                    'original_price' => $product->original_price * 12,
                    'priority' => 3,
                ],
            ];
            
            foreach ($durations as $durationData) {
                $product->durations()->create($durationData);
            }
        }
        
        // Создание скидки на категорию VIP
        if ($vipCategory) {
            Discount::create([
                'name' => 'Скидка на все VIP',
                'type' => 'percentage',
                'value' => 15,
                'category_id' => $vipCategory->id,
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'is_active' => true,
                'priority' => 1,
            ]);
        }
        
        // Создание RCON товаров
        $adminCategory = Category::where('slug', 'admin-access')->first();
        
        if ($adminCategory) {
            $rconProduct = Product::create([
                'name' => 'RCON Доступ',
                'slug' => 'rcon-access',
                'category_id' => $adminCategory->id,
                'driver_type' => 'rcon',
                'price' => 500.00,
                'active' => true,
                'server_mode' => 'specific',
                'description' => 'Полный RCON доступ к серверу для управления',
                'features' => json_encode([
                    ['text' => 'Полный доступ к командам', 'checked' => true],
                    ['text' => 'Управление картами', 'checked' => true],
                    ['text' => 'Баны и кики', 'checked' => true],
                    ['text' => 'Мониторинг сервера', 'checked' => true],
                    ['text' => 'Техническая поддержка', 'checked' => true],
                ]),
                'priority' => 1,
            ]);
            
            $rconProduct->durations()->create([
                'name' => '1 месяц',
                'duration_months' => 1,
                'price' => 500.00,
                'priority' => 1,
            ]);
        }
        
        $this->command->info('Данные магазина успешно созданы!');
    }
}