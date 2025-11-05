<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;
    protected string $event;

    public function __construct(Order $order, string $event)
    {
        $this->order = $order;
        $this->event = $event;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $subject = match($this->event) {
            'placed' => 'سفارش شما ثبت شد',
            'paid' => 'پرداخت موفق',
            'fulfilled' => 'سفارش شما ارسال شد',
            'cancelled' => 'سفارش لغو شد',
            default => 'وضعیت سفارش تغییر کرد',
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('سلام ' . $notifiable->name)
            ->line("سفارش شما با شماره {$this->order->order_number} {$subject}")
            ->line("مبلغ: {$this->order->grand_total} {$this->order->currency_code}")
            ->action('مشاهده سفارش', config('app.url') . '/orders/' . $this->order->id)
            ->line('با تشکر از خرید شما');
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'event' => $this->event,
            'message' => $this->getMessage(),
            'amount' => $this->order->grand_total,
            'currency' => $this->order->currency_code,
        ];
    }

    protected function getMessage()
    {
        return match($this->event) {
            'placed' => "سفارش شما با شماره {$this->order->order_number} ثبت شد.",
            'paid' => "پرداخت سفارش {$this->order->order_number} با موفقیت انجام شد.",
            'fulfilled' => "سفارش {$this->order->order_number} شما ارسال شد.",
            'cancelled' => "سفارش {$this->order->order_number} لغو شد.",
            default => "وضعیت سفارش {$this->order->order_number} تغییر کرد.",
        };
    }
}

