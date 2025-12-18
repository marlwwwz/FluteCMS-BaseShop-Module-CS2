<?php

namespace App\Modules\Shop\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Shop\Models\Discount;
use Carbon\Carbon;

class UpdateDiscounts extends Command
{
    protected $signature = 'shop:update-discounts';
    protected $description = 'Обновить статусы скидок';
    
    public function handle()
    {
        $now = Carbon::now();
        
        // Деактивировать просроченные скидки
        $expired = Discount::where('is_active', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<', $now)
            ->update(['is_active' => false]);
            
        $this->info("Деактивировано скидок: {$expired}");
        
        // Активировать скидки, у которых наступила дата начала
        $activated = Discount::where('is_active', false)
            ->whereNotNull('start_date')
            ->where('start_date', '<=', $now)
            ->where(function($query) use ($now) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $now);
            })
            ->update(['is_active' => true]);
            
        $this->info("Активировано скидок: {$activated}");
        
        // Уведомление о скором окончании скидок
        $soonExpiring = Discount::where('is_active', true)
            ->whereNotNull('end_date')
            ->where('end_date', '>=', $now)
            ->where('end_date', '<=', $now->addDays(3))
            ->get();
            
        foreach ($soonExpiring as $discount) {
            $this->warn("Скидка '{$discount->name}' заканчивается {$discount->end_date->format('d.m.Y')}");
        }
        
        return Command::SUCCESS;
    }
}