<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantPrice;
use App\Models\Inventory;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:191', 'unique:product_variants'],
            'option_values' => ['nullable', 'array'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
            'prices' => ['required', 'array'],
            'prices.*.currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.compare_at_amount' => ['nullable', 'numeric', 'min:0'],
            'prices.*.start_at' => ['nullable', 'date'],
            'prices.*.end_at' => ['nullable', 'date', 'after:start_at'],
            'inventory' => ['sometimes', 'array'],
            'inventory.quantity_on_hand' => ['required_with:inventory', 'integer', 'min:0'],
            'inventory.quantity_reserved' => ['sometimes', 'integer', 'min:0'],
            'inventory.low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
        ]);

        $variant = $product->variants()->create([
            'sku' => $validated['sku'],
            'option_values' => $validated['option_values'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // افزودن قیمت‌ها
        foreach ($validated['prices'] as $priceData) {
            $variant->prices()->create([
                'currency_code' => $priceData['currency_code'],
                'amount' => $priceData['amount'],
                'compare_at_amount' => $priceData['compare_at_amount'] ?? null,
                'start_at' => $priceData['start_at'] ?? null,
                'end_at' => $priceData['end_at'] ?? null,
            ]);
        }

        // افزودن موجودی
        if (isset($validated['inventory'])) {
            $variant->inventory()->create([
                'quantity_on_hand' => $validated['inventory']['quantity_on_hand'],
                'quantity_reserved' => $validated['inventory']['quantity_reserved'] ?? 0,
                'low_stock_threshold' => $validated['inventory']['low_stock_threshold'] ?? 0,
            ]);
        }

        return response()->json([
            'message' => 'Product variant created successfully.',
            'variant' => $variant->load(['prices', 'inventory']),
        ], 201);
    }

    public function update(Request $request, $productId, $variantId)
    {
        $variant = ProductVariant::where('product_id', $productId)
            ->findOrFail($variantId);

        $validated = $request->validate([
            'sku' => ['sometimes', 'string', 'max:191', 'unique:product_variants,sku,' . $variantId],
            'option_values' => ['nullable', 'array'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
            'prices' => ['sometimes', 'array'],
            'prices.*.currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.compare_at_amount' => ['nullable', 'numeric', 'min:0'],
            'prices.*.start_at' => ['nullable', 'date'],
            'prices.*.end_at' => ['nullable', 'date', 'after:start_at'],
            'inventory' => ['sometimes', 'array'],
            'inventory.quantity_on_hand' => ['required_with:inventory', 'integer', 'min:0'],
            'inventory.quantity_reserved' => ['sometimes', 'integer', 'min:0'],
            'inventory.low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
        ]);

        $variant->update([
            'sku' => $validated['sku'] ?? $variant->sku,
            'option_values' => $validated['option_values'] ?? $variant->option_values,
            'barcode' => $validated['barcode'] ?? $variant->barcode,
            'is_active' => $validated['is_active'] ?? $variant->is_active,
        ]);

        // به‌روزرسانی قیمت‌ها
        if (isset($validated['prices'])) {
            // حذف قیمت‌های قدیمی و افزودن جدید
            $variant->prices()->delete();
            foreach ($validated['prices'] as $priceData) {
                $variant->prices()->create([
                    'currency_code' => $priceData['currency_code'],
                    'amount' => $priceData['amount'],
                    'compare_at_amount' => $priceData['compare_at_amount'] ?? null,
                    'start_at' => $priceData['start_at'] ?? null,
                    'end_at' => $priceData['end_at'] ?? null,
                ]);
            }
        }

        // به‌روزرسانی موجودی
        if (isset($validated['inventory'])) {
            $variant->inventory()->updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'quantity_on_hand' => $validated['inventory']['quantity_on_hand'],
                    'quantity_reserved' => $validated['inventory']['quantity_reserved'] ?? 0,
                    'low_stock_threshold' => $validated['inventory']['low_stock_threshold'] ?? 0,
                ]
            );
        }

        return response()->json([
            'message' => 'Product variant updated successfully.',
            'variant' => $variant->fresh(['prices', 'inventory']),
        ], 200);
    }

    public function destroy($productId, $variantId)
    {
        $variant = ProductVariant::where('product_id', $productId)
            ->findOrFail($variantId);

        // بررسی اینکه آیا variant در سفارش استفاده شده یا نه
        if ($variant->orderItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete variant with existing orders.',
            ], 422);
        }

        $variant->delete();

        return response()->json([
            'message' => 'Product variant deleted successfully.',
        ], 200);
    }
}

