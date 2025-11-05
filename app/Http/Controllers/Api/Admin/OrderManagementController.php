<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * لیست سفارش‌ها
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.variant.product', 'addresses', 'payments']);

        // فیلتر بر اساس وضعیت
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // فیلتر بر اساس تاریخ
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // جستجو
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // مرتب‌سازی
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        return response()->json([
            'orders' => $orders,
        ], 200);
    }

    /**
     * جزئیات سفارش
     */
    public function show($id)
    {
        $order = Order::with(['user', 'items.variant.product', 'addresses', 'payments.transactions'])
            ->findOrFail($id);

        return response()->json([
            'order' => $order,
        ], 200);
    }

    /**
     * به‌روزرسانی وضعیت سفارش
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:pending,paid,fulfilled,cancelled,refunded'],
        ]);

        $order = Order::findOrFail($id);
        
        if ($request->status === 'fulfilled' && $order->status === 'paid') {
            $this->orderService->fulfillOrder($order);
        } elseif ($request->status === 'cancelled') {
            $this->orderService->cancelOrder($order);
        } else {
            $order->update(['status' => $request->status]);
        }

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => $order->fresh(),
        ], 200);
    }
}

