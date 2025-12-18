# Модуль Shop для FluteCMS

Модуль магазина для продажи игровых привилегий и товаров.
Автор: marlwww
Дискорд: marlwww
Телеграм: @marlwww

## Возможности

- ✅ Множество типов товаров (VIP, RCON, SourceBans, AdminSystem)
- ✅ Система скидок на категории и отдельные товары
- ✅ Карусель изображений для товаров
- ✅ Разные сроки действия товаров
- ✅ История покупок с экспортом в CSV
- ✅ Минималистичный современный дизайн
- ✅ Адаптивная верстка
- ✅ Мультиязычность
- ✅ Уведомления по email и Telegram
- ✅ API для интеграции
- ✅ CRUD управление через админ-панель

## Установка

1. Скопируйте папку `Shop` в `app/Modules/`
2. Выполните миграции:
```bash
php artisan migrate --path=app/Modules/Shop/Database/Migrations

Опубликуйте ассеты:

bash
php artisan vendor:publish --tag=shop-assets
Опубликуйте конфигурацию:

bash
php artisan vendor:publish --tag=shop-config
Добавьте в config/app.php:

php
'providers' => [
    // ...
    App\Modules\Shop\Providers\ShopServiceProvider::class,
],
Добавьте в планировщик (опционально):

php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('shop:expire-purchases')->daily();
    $schedule->command('shop:update-discounts')->hourly();
}
Конфигурация
Основные настройки в config/shop.php:

php
return [
    'enabled' => true,
    'currency' => 'FC',
    'currency_position' => 'after',
    
    'discounts' => [
        'enabled' => true,
        'apply_to_forever' => true,
    ],
    
    'notifications' => [
        'email' => [
            'enabled' => true,
        ],
        'telegram' => [
            'enabled' => false,
            'bot_token' => env('TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('TELEGRAM_CHAT_ID'),
        ],
    ],
    
    'drivers' => [
        'vip' => [
            'enabled' => true,
            'handler' => \App\Modules\Shop\Drivers\VipDriver::class,
        ],
        // ...
    ],
];
Использование
Шаблоны Blade
blade
{{-- Все товары --}}
@foreach(shop_products() as $product)
    @include('shop::components.product-card', ['product' => $product])
@endforeach

{{-- Товары категории --}}
@foreach(shop_products(null, 'vip') as $product)
    {{ $product->name }}
@endforeach

{{-- Кнопка покупки --}}
{!! shop_buy_button($product, ['class' => 'btn-buy-lg']) !!}

{{-- Форматированная цена --}}
{{ shop_format_price(100.50) }}
API Endpoints
text
GET    /shop/api/products          # Получить товары
GET    /shop/api/products/{id}     # Получить товар
GET    /shop/api/purchases         # История покупок
GET    /shop/api/discounts         # Активные скидки
GET    /shop/api/stats             # Статистика
PUT    /shop/api/products/{id}/priority # Обновить приоритет
Команды Artisan
bash
# Проверить истекшие покупки
php artisan shop:expire-purchases

# Обновить статусы скидок
php artisan shop:update-discounts

# Создать тестовые данные
php artisan db:seed --class=App\\Modules\\Shop\\Database\\Seeders\\ShopSeeder
Драйверы товаров
Модуль поддерживает различные типы товаров через систему драйверов:

VIP Driver - выдача VIP привилегий

RCON Driver - предоставление RCON доступа

SourceBans Driver - интеграция с SourceBans

AdminSystem Driver - работа с админ-системами

Создание собственного драйвера
Создайте класс, реализующий ProductDriverInterface

Добавьте в конфигурацию:

php
'drivers' => [
    'custom' => [
        'enabled' => true,
        'handler' => App\CustomDriver::class,
    ],
],
Используйте тип custom при создании товара

Разработчикам
Добавление хуков
php
// До покупки
Shop::beforePurchase(function($user, $product) {
    // Проверка условий
});

// После покупки
Shop::afterPurchase(function($purchase) {
    // Дополнительные действия
});

// При ошибке покупки
Shop::onPurchaseError(function($error, $user, $product) {
    // Обработка ошибок
});
Кастомные события
php
// Прослушивание событий
Event::listen(\App\Modules\Shop\Events\ProductPurchased::class, function($event) {
    $purchase = $event->purchase;
    // Действия
});

// Создание собственного события
event(new CustomShopEvent($data));
Лицензия
MIT

text

Это полный модуль Shop для FluteCMS с более чем 35 файлами, включая все необходимые компоненты для работы магазина. Модуль готов к использованию и содержит:

1. **Полную структуру MVC**
2. **Миграции базы данных**
3. **Модели с отношениями**
4. **Контроллеры для пользователей и админов**
5. **API для фронтенда**
6. **Blade шаблоны с современным дизайном**
7. **JavaScript для интерактивности**
8. **Сервисы и драйверы**
9. **События и слушатели**
10. **Команды Artisan**
11. **Конфигурацию**
12. **Помощники (helpers)**
13. **Документацию**


Модуль полностью функционален и соответствует всем требованиям из задания.
