<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * ارسال اعلان سفارش
     */
    public function sendOrderNotification(Order $order, $event)
    {
        $user = $order->user;

        // Email Notification
        $this->sendOrderEmail($order, $event);

        // SMS Notification
        if ($user->phone) {
            $this->sendOrderSMS($order, $event);
        }

        // Push Notification
        $this->sendOrderPush($order, $event);

        // Database Notification
        $user->notify(new \App\Notifications\OrderNotification($order, $event));
    }

    /**
     * ارسال ایمیل سفارش
     */
    protected function sendOrderEmail(Order $order, $event)
    {
        try {
            $user = $order->user;
            
            // TODO: ساخت Mail class
            // Mail::to($user->email)->send(new OrderStatusMail($order, $event));
            
            Log::info('Order email sent', [
                'order_id' => $order->id,
                'event' => $event,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ارسال SMS سفارش
     */
    protected function sendOrderSMS(Order $order, $event)
    {
        try {
            $user = $order->user;
            $message = $this->getOrderSMSMessage($order, $event);

            // TODO: استفاده از سرویس SMS (مثل Kavenegar)
            // $this->sendSMS($user->phone, $message);

            Log::info('Order SMS sent', [
                'order_id' => $order->id,
                'event' => $event,
                'phone' => $user->phone,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order SMS', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ارسال Push Notification
     */
    protected function sendOrderPush(Order $order, $event)
    {
        try {
            $user = $order->user;

            // TODO: ارسال Push Notification
            // Notification::send($user, new OrderPushNotification($order, $event));

            Log::info('Order push notification sent', [
                'order_id' => $order->id,
                'event' => $event,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * دریافت پیام SMS سفارش
     */
    protected function getOrderSMSMessage(Order $order, $event)
    {
        $messages = [
            'placed' => "سفارش شما با شماره {$order->order_number} ثبت شد. مبلغ: {$order->grand_total} ریال",
            'paid' => "پرداخت سفارش {$order->order_number} با موفقیت انجام شد.",
            'fulfilled' => "سفارش {$order->order_number} شما ارسال شد.",
            'cancelled' => "سفارش {$order->order_number} لغو شد.",
        ];

        return $messages[$event] ?? "وضعیت سفارش {$order->order_number} تغییر کرد.";
    }

    /**
     * هشدار بازگشت به فروشگاه (Cart Abandonment Recovery)
     */
    public function sendCartAbandonmentReminder(Cart $cart)
    {
        if (!$cart->user_id) {
            return;
        }

        $user = $cart->user;
        $itemsCount = $cart->items()->count();

        if ($itemsCount > 0) {
            // TODO: ارسال ایمیل یادآوری
            // Mail::to($user->email)->send(new CartAbandonmentReminder($cart));

            Log::info('Cart abandonment reminder sent', [
                'cart_id' => $cart->id,
                'user_id' => $user->id,
                'items_count' => $itemsCount,
            ]);
        }
    }

    /**
     * ارسال اعلان موجودی کم
     */
    public function sendLowStockAlert($product, $variant, $inventory)
    {
        // TODO: ارسال به ادمین‌ها
        // $admins = User::whereHas('roles', function ($q) {
        //     $q->where('slug', 'admin');
        // })->get();

        // Notification::send($admins, new LowStockAlert($product, $variant, $inventory));

        Log::warning('Low stock alert', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'available' => $inventory->quantity_on_hand - $inventory->quantity_reserved,
        ]);
    }
}

