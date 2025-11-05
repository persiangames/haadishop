<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCartAbandonmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Cart $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function handle(NotificationService $notificationService)
    {
        // بررسی اینکه آیا سبد خرید هنوز خالی نشده
        if ($this->cart->items()->count() > 0) {
            $notificationService->sendCartAbandonmentReminder($this->cart);
        }
    }
}

