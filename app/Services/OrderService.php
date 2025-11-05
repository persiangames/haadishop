<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddress;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Services\AffiliateService;
use App\Services\LotteryService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected CartService $cartService;
    protected AffiliateService $affiliateService;
    protected LotteryService $lotteryService;
    protected NotificationService $notificationService;

    public function __construct(
        CartService $cartService,
        AffiliateService $affiliateService,
        LotteryService $lotteryService,
        NotificationService $notificationService
    ) {
        $this->cartService = $cartService;
        $this->affiliateService = $affiliateService;
        $this->lotteryService = $lotteryService;
        $this->notificationService = $notificationService;
    }

    public function createOrderFromCart($cart, $billingAddress, $shippingAddress = null, $couponCode = null)
    {
        if ($cart->items()->count() === 0) {
            throw new \Exception('Cart is empty.');
        }

        // استفاده از shipping address اگر داده شده، در غیر این صورت از billing
        $shippingAddress = $shippingAddress ?? $billingAddress;

        return DB::transaction(function () use ($cart, $billingAddress, $shippingAddress, $couponCode) {
            // محاسبه مبالغ
            $subtotal = $this->cartService->getCartTotal($cart);
            $discountTotal = 0; // TODO: محاسبه کوپن
            $taxTotal = 0; // TODO: محاسبه مالیات
            $shippingTotal = 0; // TODO: محاسبه هزینه ارسال
            $grandTotal = $subtotal - $discountTotal + $taxTotal + $shippingTotal;

            // ایجاد سفارش
            $order = Order::create([
                'user_id' => $cart->user_id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'currency_code' => $cart->currency_code,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'shipping_total' => $shippingTotal,
                'grand_total' => $grandTotal,
                'paid_total' => 0,
                'due_total' => $grandTotal,
                'placed_at' => now(),
            ]);

            // افزودن آدرس‌ها
            $order->addresses()->create([
                'type' => 'billing',
                'name' => $billingAddress['name'],
                'phone' => $billingAddress['phone'],
                'country' => $billingAddress['country'],
                'province' => $billingAddress['province'],
                'city' => $billingAddress['city'],
                'address_line' => $billingAddress['address_line'],
                'postal_code' => $billingAddress['postal_code'],
            ]);

            $order->addresses()->create([
                'type' => 'shipping',
                'name' => $shippingAddress['name'],
                'phone' => $shippingAddress['phone'],
                'country' => $shippingAddress['country'],
                'province' => $shippingAddress['province'],
                'city' => $shippingAddress['city'],
                'address_line' => $shippingAddress['address_line'],
                'postal_code' => $shippingAddress['postal_code'],
            ]);

            // افزودن آیتم‌های سفارش و رزرو موجودی
            foreach ($cart->items as $cartItem) {
                $variant = $cartItem->variant;
                $inventory = $variant->inventory;

                // رزرو موجودی
                if ($inventory) {
                    $inventory->quantity_reserved += $cartItem->quantity;
                    $inventory->quantity_on_hand -= $cartItem->quantity;
                    $inventory->save();
                }

                // افزودن به سفارش
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $cartItem->line_total,
                ]);
            }

            // پاک کردن سبد خرید
            $this->cartService->clearCart($cart);

            // ایجاد referral اگر affiliate_code وجود دارد
            $affiliateCode = session('affiliate_code') ?? request()->get('ref');
            if ($affiliateCode) {
                $this->affiliateService->createReferral($order, $affiliateCode);
            }

            // ارسال اعلان سفارش
            $this->notificationService->sendOrderNotification($order, 'placed');

            return $order->load(['items.variant.product', 'addresses', 'user']);
        });
    }

    public function cancelOrder(Order $order)
    {
        if ($order->status === 'cancelled') {
            throw new \Exception('Order is already cancelled.');
        }

        if ($order->status === 'fulfilled') {
            throw new \Exception('Cannot cancel fulfilled order.');
        }

        return DB::transaction(function () use ($order) {
            // بازگرداندن موجودی
            foreach ($order->items as $item) {
                $variant = $item->variant;
                $inventory = $variant->inventory;

                if ($inventory) {
                    $inventory->quantity_reserved -= $item->quantity;
                    $inventory->quantity_on_hand += $item->quantity;
                    $inventory->save();
                }
            }

            $order->update([
                'status' => 'cancelled',
            ]);

            // ارسال اعلان لغو سفارش
            $this->notificationService->sendOrderNotification($order, 'cancelled');

            return $order;
        });
    }

    public function fulfillOrder(Order $order)
    {
        if ($order->status !== 'paid') {
            throw new \Exception('Order must be paid before fulfillment.');
        }

        $order->update([
            'status' => 'fulfilled',
        ]);

        // ارسال اعلان ارسال سفارش
        $this->notificationService->sendOrderNotification($order, 'fulfilled');

        return $order;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . strtoupper(uniqid());
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}

