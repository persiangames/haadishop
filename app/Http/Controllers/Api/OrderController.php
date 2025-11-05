<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['items.variant.product', 'addresses', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'orders' => $orders,
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->with(['items.variant.product', 'addresses', 'payments.transactions'])
            ->findOrFail($id);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'currency_code' => $order->currency_code,
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'tax_total' => $order->tax_total,
                'shipping_total' => $order->shipping_total,
                'grand_total' => $order->grand_total,
                'paid_total' => $order->paid_total,
                'due_total' => $order->due_total,
                'placed_at' => $order->placed_at,
                'items' => $order->items->map(function ($item) {
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
                'addresses' => $order->addresses,
                'payments' => $order->payments,
            ],
        ], 200);
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->findOrFail($id);

        try {
            $order = $this->orderService->cancelOrder($order);

            return response()->json([
                'message' => 'Order cancelled successfully.',
                'order' => $order,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

