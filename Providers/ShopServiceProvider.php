<?php

namespace App\Modules\Shop\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Modules\Shop\Console\Commands\ExpirePurchases;
use App\Modules\Shop\Console\Commands\UpdateDiscounts;

class ShopServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/shop.php', 'shop'
        );
        
        // Регистрация помощников
        if (file_exists($helpers = __DIR__.'/../helpers.php')) {
            require_once $helpers;
        }
    }
    
    public function boot()
    {
        // Публикация конфигурации
        $this->publishes([
            __DIR__.'/../config/shop.php' => config_path('shop.php'),
        ], 'shop-config');
        
        // Публикация ассетов
        $this->publishes([
            __DIR__.'/../Resources/assets' => public_path('modules/shop'),
        ], 'shop-assets');
        
        // Публикация миграций
        $this->publishes([
            __DIR__.'/../Database/Migrations' => database_path('migrations'),
        ], 'shop-migrations');
        
        // Регистрация команд
        $this->commands([
            ExpirePurchases::class,
            UpdateDiscounts::class,
        ]);
        
        // Планировщик задач
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Ежедневная проверка истекших покупок
            $schedule->command('shop:expire-purchases')->daily();
            
            // Ежечасное обновление статусов скидок
            $schedule->command('shop:update-discounts')->hourly();
            
            // Очистка старых логов раз в неделю
            $schedule->command('shop:cleanup-logs')->weekly();
        });
        
        // Регистрация маршрутов API
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        
        // Регистрация переводов
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'shop');
        
        // Регистрация представлений
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'shop');
        
        // Регистрация миграций
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}