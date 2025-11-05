<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);
        $currencyCode = $request->get('currency', 'IRR');

        $cart = $this->cartService->getOrCreateCart($user, $sessionId, $currencyCode);
        $items = $this->cartService->getCartItems($cart);
        $total = $this->cartService->getCartTotal($cart);

        return response()->json([
            'cart' => [
                'id' => $cart->id,
                'currency_code' => $cart->currency_code,
                'items_count' => $items->count(),
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'variant' => [
                            'id' => $item->variant->id,
                            'sku' => $item->variant->sku,
                            'option_values' => $item->variant->option_values,
                        ],
                        'product' => [
                            'id' => $item->variant->product->id,
                            'slug' => $item->variant->product->slug,
                            'title' => $item->variant->product->translation(app()->getLocale())->title ?? null,
                        ],
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => $item->line_total,
                    ];
                }),
                'subtotal' => $total,
            ],
        ], 200);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);
        $currencyCode = $request->get('currency', 'IRR');

        $cart = $this->cartService->getOrCreateCart($user, $sessionId, $currencyCode);

        try {
            $item = $this->cartService->addItem(
                $cart,
                $request->variant_id,
                $request->quantity,
                $currencyCode
            );

            return response()->json([
                'message' => 'Item added to cart successfully.',
                'item' => $item,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);
        $currencyCode = $request->get('currency', 'IRR');

        $cart = $this->cartService->getOrCreateCart($user, $sessionId, $currencyCode);

        try {
            $item = $this->cartService->updateItem($cart, $itemId, $request->quantity);

            if (!$item) {
                return response()->json([
                    'message' => 'Item removed from cart.',
                ], 200);
            }

            return response()->json([
                'message' => 'Item updated successfully.',
                'item' => $item,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function removeItem(Request $request, $itemId)
    {
        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);
        $currencyCode = $request->get('currency', 'IRR');

        $cart = $this->cartService->getOrCreateCart($user, $sessionId, $currencyCode);

        try {
            $this->cartService->removeItem($cart, $itemId);

            return response()->json([
                'message' => 'Item removed from cart successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);
        $currencyCode = $request->get('currency', 'IRR');

        $cart = $this->cartService->getOrCreateCart($user, $sessionId, $currencyCode);

        $this->cartService->clearCart($cart);

        return response()->json([
            'message' => 'Cart cleared successfully.',
        ], 200);
    }
}

