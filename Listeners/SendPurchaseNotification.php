<?php

namespace App\Modules\Shop\Listeners;

use App\Modules\Shop\Events\ProductPurchased;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseConfirmation;

class SendPurchaseNotification implements ShouldQueue
{
    public function handle(ProductPurchased $event)
    {
        $purchase = $event->purchase;
        $user = $event->user;
        
        // Отправляем email уведомление
        if ($user->email && config('shop.notifications.email.enabled', true)) {
            Mail::to($user->email)->send(new PurchaseConfirmation($purchase));
        }
        
        // Отправляем уведомление в FluteCMS
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => 'Покупка завершена',
                'message' => sprintf(
                    'Вы успешно приобрели "%s" за %s %s',
                    $purchase->product->name,
                    $purchase->price,
                    config('shop.currency', 'FC')
                ),
                'type' => 'success',
                'read' => false,
            ]);
        }
        
        // Отправляем в Telegram, если настроено
        $this->sendToTelegram($purchase);
        
        // Логируем уведомление
        \Log::info('Purchase notification sent', [
            'purchase_id' => $purchase->id,
            'user_id' => $user->id,
            'email_sent' => !empty($user->email),
        ]);
    }
    
    private function sendToTelegram($purchase)
    {
        $telegramBotToken = config('shop.notifications.telegram.bot_token');
        $telegramChatId = config('shop.notifications.telegram.chat_id');
        
        if (!$telegramBotToken || !$telegramChatId) {
            return;
        }
        
        $message = sprintf(
            "🛒 Новая покупка #%d\n".
            "👤 Пользователь: %s\n".
            "📦 Товар: %s\n".
            "💰 Цена: %s %s\n".
            "🕐 Дата: %s",
            $purchase->id,
            $purchase->user->name,
            $purchase->product->name,
            $purchase->price,
            config('shop.currency', 'FC'),
            $purchase->created_at->format('d.m.Y H:i')
        );
        
        try {
            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$telegramBotToken}/sendMessage", [
                'chat_id' => $telegramChatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            \Log::error('Telegram notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}