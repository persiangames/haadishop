<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected CartService $cartService;
    protected OrderService $orderService;

    public function __construct(CartService $cartService, OrderService $orderService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'billing_address' => ['required', 'array'],
            'billing_address.name' => ['required', 'string', 'max:255'],
            'billing_address.phone' => ['required', 'string', 'max:32'],
            'billing_address.country' => ['required', 'string', 'max:64'],
            'billing_address.province' => ['required', 'string', 'max:64'],
            'billing_address.city' => ['required', 'string', 'max:64'],
            'billing_address.address_line' => ['required', 'string', 'max:255'],
            'billing_address.postal_code' => ['required', 'string', 'max:32'],
            'shipping_address' => ['sometimes', 'array'],
            'shipping_address.name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.phone' => ['required_with:shipping_address', 'string', 'max:32'],
            'shipping_address.country' => ['required_with:shipping_address', 'string', 'max:64'],
            'shipping_address.province' => ['required_with:shipping_address', 'string', 'max:64'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:64'],
            'shipping_address.address_line' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.postal_code' => ['required_with:shipping_address', 'string', 'max:32'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);
        $currencyCode = $request->get('currency', 'IRR');

        $cart = $this->cartService->getOrCreateCart($user, $sessionId, $currencyCode);

        if ($cart->items()->count() === 0) {
            return response()->json([
                'message' => 'Cart is empty.',
            ], 422);
        }

        try {
            $order = $this->orderService->createOrderFromCart(
                $cart,
                $request->billing_address,
                $request->shipping_address ?? null,
                $request->coupon_code ?? null
            );

            return response()->json([
                'message' => 'Order created successfully.',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'grand_total' => $order->grand_total,
                    'currency_code' => $order->currency_code,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

