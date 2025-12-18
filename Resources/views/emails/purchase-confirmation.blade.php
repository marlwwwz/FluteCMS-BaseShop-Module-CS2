@component('mail::message')
# Подтверждение покупки

Спасибо за покупку в нашем магазине! Ваш заказ успешно обработан.

## Детали покупки
@component('mail::panel')
**Номер заказа:** #{{ $purchase->id }}  
**Товар:** {{ $product->name }}  
**Цена:** {{ $purchase->price }} {{ $currency }}  
**Дата покупки:** {{ $purchase->created_at->format('d.m.Y H:i') }}  
**Статус:** Завершено  
@if($purchase->discount_amount > 0)
**Скидка:** {{ $purchase->discount_amount }} {{ $currency }}  
@endif
@endcomponent

## Информация о сервере
@if($purchase->server)
**Сервер:** {{ $purchase->server->name }}  
**IP адрес:** {{ $purchase->server->ip }}:{{ $purchase->server->port }}  
@endif

## Срок действия
@if($is_forever)
✅ **Действует:** Навсегда  
@elseif($expires_at)
✅ **Действует до:** {{ $expires_at->format('d.m.Y H:i') }}  
⏳ **Осталось:** {{ $purchase->remaining_time }}  
@endif

## Дополнительная информация
@if($product->driver_type === 'vip')
- Ваш VIP активирован автоматически
- Для доступа к привилегиям перезайдите на сервер
- При возникновении проблем обратитесь к администрации
@endif

@if($product->driver_type === 'rcon')
- RCON доступ активирован
- Используйте полученные данные для подключения
@endif

@component('mail::button', ['url' => route('shop.index'), 'color' => 'primary'])
Перейти в магазин
@endcomponent

С уважением,  
Команда {{ config('app.name') }}
@endcomponent