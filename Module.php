<?php

namespace App\Modules\Shop;

use App\Core\Module\ModuleBaseServiceProvider;

class Module extends ModuleBaseServiceProvider
{
    protected string $name = 'Shop';
    protected string $description = 'Модуль магазина для покупки VIP, RCON, SourceBans, AdminSystem';
    protected string $version = '1.0.0';
    protected string $author = 'FluteCMS';
    
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'shop');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'shop');
        
        $this->publishes([
            __DIR__.'/Resources/assets' => public_path('modules/shop'),
        ], 'shop-assets');
        
        // Регистрация событий
        $this->registerEvents();
    }
    
    public function register(): void
    {
        $this->app->singleton(ShopService::class);
        $this->app->singleton(DiscountService::class);
        $this->app->singleton(PurchaseService::class);
        
        // Регистрация настроек модуля
        $this->registerSettings();
    }
    
    private function registerEvents(): void
    {
        $events = [
            Events\ProductPurchased::class => [
                Listeners\SendPurchaseNotification::class,
            ],
        ];
        
        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {
                \Event::listen($event, $listener);
            }
        }
    }
    
    private function registerSettings(): void
    {
        $settings = [
            'shop.enabled' => [
                'type' => 'boolean',
                'default' => true,
                'name' => 'Включить магазин',
                'description' => 'Активировать модуль магазина'
            ],
            'shop.currency' => [
                'type' => 'string',
                'default' => 'FC',
                'name' => 'Валюта магазина',
                'description' => 'Символ валюты для отображения цен'
            ],
            'shop.min_purchase' => [
                'type' => 'integer',
                'default' => 0,
                'name' => 'Минимальная сумма покупки',
                'description' => 'Минимальная сумма для оформления заказа'
            ],
        ];
        
        foreach ($settings as $key => $setting) {
            config()->set($key, $setting['default']);
        }
    }
    
    public function getAdminMenu(): array
    {
        return [
            [
                'title' => __('shop.menu.shop'),
                'icon' => 'ph-shopping-cart',
                'url' => route('shop.admin.index'),
                'permission' => 'shop.manage',
                'children' => [
                    [
                        'title' => __('shop.menu.products'),
                        'url' => route('shop.admin.products.index'),
                        'permission' => 'shop.products.manage',
                    ],
                    [
                        'title' => __('shop.menu.purchases'),
                        'url' => route('shop.admin.purchases.index'),
                        'permission' => 'shop.purchases.view',
                    ],
                    [
                        'title' => __('shop.menu.discounts'),
                        'url' => route('shop.admin.discounts.index'),
                        'permission' => 'shop.discounts.manage',
                    ],
                ]
            ]
        ];
    }
}