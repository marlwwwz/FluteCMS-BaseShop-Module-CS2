<?php

namespace App\Modules\Shop\Mail;

use App\Modules\Shop\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseConfirmation extends Mailable
{
    use Queueable, SerializesModels;
    
    public $purchase;
    public $user;
    public $product;
    
    public function __construct(Purchase $purchase)
    {
        $this->purchase = $purchase;
        $this->user = $purchase->user;
        $this->product = $purchase->product;
    }
    
    public function build()
    {
        return $this->subject('Подтверждение покупки - ' . config('app.name'))
            ->markdown('shop::emails.purchase-confirmation')
            ->with([
                'purchase' => $this->purchase,
                'user' => $this->user,
                'product' => $this->product,
                'currency' => config('shop.currency', 'FC'),
                'expires_at' => $this->purchase->expires_at,
                'is_forever' => $this->purchase->is_forever,
            ]);
    }
}