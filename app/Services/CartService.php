<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function getOrCreateCart($user = null, $sessionId = null, $currencyCode = 'IRR')
    {
        if ($user) {
            $cart = Cart::where('user_id', $user->id)
                ->where('currency_code', $currencyCode)
                ->first();

            if (!$cart) {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'currency_code' => $currencyCode,
                    'locale' => app()->getLocale(),
                ]);
            }
        } else {
            $cart = Cart::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->where('currency_code', $currencyCode)
                ->first();

            if (!$cart) {
                $cart = Cart::create([
                    'session_id' => $sessionId,
                    'currency_code' => $currencyCode,
                    'locale' => app()->getLocale(),
                ]);
            }
        }

        return $cart;
    }

    public function addItem($cart, $variantId, $quantity, $currencyCode = 'IRR')
    {
        $variant = ProductVariant::with('product', 'inventory')->findOrFail($variantId);

        // بررسی موجودی
        if (!$variant->inventory || $variant->inventory->available_quantity < $quantity) {
            throw new \Exception('Insufficient stock available.');
        }

        // بررسی فعال بودن variant
        if (!$variant->is_active || !$variant->product->is_published) {
            throw new \Exception('Product variant is not available.');
        }

        // دریافت قیمت
        $price = $variant->getPrice($currencyCode);
        if (!$price) {
            throw new \Exception('Price not available for this currency.');
        }

        // بررسی اینکه آیا این variant قبلاً در سبد خرید است
        $existingItem = $cart->items()->where('product_variant_id', $variantId)->first();

        if ($existingItem) {
            // به‌روزرسانی تعداد
            $newQuantity = $existingItem->quantity + $quantity;

            // بررسی موجودی مجدد
            if ($variant->inventory->available_quantity < $newQuantity) {
                throw new \Exception('Insufficient stock available.');
            }

            $existingItem->update([
                'quantity' => $newQuantity,
                'unit_price' => $price->amount,
                'line_total' => $price->amount * $newQuantity,
            ]);

            return $existingItem;
        } else {
            // افزودن آیتم جدید
            $item = $cart->items()->create([
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $price->amount,
                'line_total' => $price->amount * $quantity,
            ]);

            return $item;
        }
    }

    public function updateItem($cart, $itemId, $quantity)
    {
        $item = $cart->items()->findOrFail($itemId);
        $variant = $item->variant;

        // بررسی موجودی
        if (!$variant->inventory || $variant->inventory->available_quantity < $quantity) {
            throw new \Exception('Insufficient stock available.');
        }

        if ($quantity <= 0) {
            $item->delete();
            return null;
        }

        $item->update([
            'quantity' => $quantity,
            'line_total' => $item->unit_price * $quantity,
        ]);

        return $item;
    }

    public function removeItem($cart, $itemId)
    {
        $item = $cart->items()->findOrFail($itemId);
        $item->delete();
        return true;
    }

    public function clearCart($cart)
    {
        $cart->items()->delete();
        return true;
    }

    public function getCartTotal($cart)
    {
        return $cart->items->sum('line_total');
    }

    public function getCartItems($cart)
    {
        return $cart->items()->with(['variant.product', 'variant.prices', 'variant.inventory'])->get();
    }
}

