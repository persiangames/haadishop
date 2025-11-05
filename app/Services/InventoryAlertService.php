<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class InventoryAlertService
{
    /**
     * بررسی موجودی‌های کم و ارسال هشدار
     */
    public function checkLowStock()
    {
        $lowStockItems = Inventory::whereRaw('(quantity_on_hand - quantity_reserved) <= low_stock_threshold')
            ->with('variant.product')
            ->get();

        foreach ($lowStockItems as $inventory) {
            $this->sendLowStockAlert($inventory);
        }

        return $lowStockItems->count();
    }

    /**
     * ارسال هشدار موجودی کم
     */
    protected function sendLowStockAlert(Inventory $inventory)
    {
        $variant = $inventory->variant;
        $product = $variant->product;
        $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;

        // Log هشدار
        Log::warning('Low stock alert', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'sku' => $variant->sku,
            'available' => $available,
            'threshold' => $inventory->low_stock_threshold,
        ]);

        // TODO: ارسال ایمیل به ادمین
        // Mail::to(config('mail.admin_email'))->send(new LowStockAlert($inventory));

        // TODO: ارسال Push Notification
        // Notification::send($admins, new LowStockAlert($inventory));
    }

    /**
     * بررسی موجودی صفر
     */
    public function checkOutOfStock()
    {
        $outOfStockItems = Inventory::whereRaw('(quantity_on_hand - quantity_reserved) <= 0')
            ->with('variant.product')
            ->get();

        foreach ($outOfStockItems as $inventory) {
            $this->sendOutOfStockAlert($inventory);
        }

        return $outOfStockItems->count();
    }

    /**
     * ارسال هشدار موجودی صفر
     */
    protected function sendOutOfStockAlert(Inventory $inventory)
    {
        $variant = $inventory->variant;
        $product = $variant->product;

        Log::error('Out of stock alert', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'sku' => $variant->sku,
        ]);

        // TODO: ارسال ایمیل به ادمین
        // TODO: غیرفعال کردن محصول
        // $product->update(['is_published' => false]);
    }
}

