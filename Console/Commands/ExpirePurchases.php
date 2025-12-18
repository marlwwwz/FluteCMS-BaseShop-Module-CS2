<?php

namespace App\Modules\Shop\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Shop\Models\Purchase;
use App\Modules\Shop\Events\PurchaseExpired;
use Carbon\Carbon;

class ExpirePurchases extends Command
{
    protected $signature = 'shop:expire-purchases';
    protected $description = 'Отметить истекшие покупки';
    
    public function handle()
    {
        $expiredPurchases = Purchase::where('status', 'completed')
            ->where('is_forever', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
        
        $count = 0;
        
        foreach ($expiredPurchases as $purchase) {
            try {
                // Обновляем статус
                $purchase->update(['status' => 'expired']);
                
                // Отправляем событие
                event(new PurchaseExpired($purchase));
                
                $count++;
                
                $this->info("Покупка #{$purchase->id} истекла");
                
            } catch (\Exception $e) {
                $this->error("Ошибка при обработке покупки #{$purchase->id}: " . $e->getMessage());
            }
        }
        
        $this->info("Обработано покупок: {$count}");
        
        // Также очищаем очень старые отмененные покупки
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $deleted = Purchase::where('status', 'failed')
            ->where('created_at', '<', $thirtyDaysAgo)
            ->delete();
            
        if ($deleted > 0) {
            $this->info("Удалено старых неудачных покупок: {$deleted}");
        }
        
        return Command::SUCCESS;
    }
}