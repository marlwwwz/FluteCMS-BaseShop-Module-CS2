<?php

namespace App\Modules\Shop\Events;

use App\Modules\Shop\Models\Purchase;
use App\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ProductPurchased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $purchase;
    public $user;
    
    public function __construct(Purchase $purchase, User $user = null)
    {
        $this->purchase = $purchase;
        $this->user = $user ?? $purchase->user;
    }
}