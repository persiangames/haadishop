<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportingService
{
    /**
     * گزارش فروش
     */
    public function getSalesReport($fromDate, $toDate, $groupBy = 'day')
    {
        $format = match($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return Order::where('status', 'paid')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('SUM(grand_total) as total_revenue'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('AVG(grand_total) as average_order_value')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * گزارش محصولات
     */
    public function getProductsReport($fromDate, $toDate)
    {
        return OrderItem::whereHas('order', function ($q) use ($fromDate, $toDate) {
            $q->where('status', 'paid')
              ->whereBetween('created_at', [$fromDate, $toDate]);
        })
        ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
        ->join('products', 'product_variants.product_id', '=', 'products.id')
        ->select(
            'products.id',
            'products.slug',
            DB::raw('SUM(order_items.quantity) as total_sold'),
            DB::raw('SUM(order_items.line_total) as total_revenue'),
            DB::raw('AVG(order_items.unit_price) as average_price')
        )
        ->groupBy('products.id', 'products.slug')
        ->orderBy('total_revenue', 'desc')
        ->get();
    }

    /**
     * گزارش کاربران
     */
    public function getUserReport($fromDate, $toDate)
    {
        return User::whereBetween('created_at', [$fromDate, $toDate])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                DB::raw('COUNT(*) as new_users')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * گزارش پرداخت‌ها
     */
    public function getPaymentReport($fromDate, $toDate)
    {
        return Payment::where('status', 'succeeded')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->select(
                'provider',
                DB::raw('COUNT(*) as total_payments'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as average_amount')
            )
            ->groupBy('provider')
            ->get();
    }

    /**
     * گزارش کامل
     */
    public function getFullReport($fromDate, $toDate)
    {
        return [
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'summary' => [
                'total_revenue' => Order::where('status', 'paid')
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->sum('grand_total'),
                'total_orders' => Order::whereBetween('created_at', [$fromDate, $toDate])
                    ->count(),
                'paid_orders' => Order::where('status', 'paid')
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->count(),
                'total_users' => User::whereBetween('created_at', [$fromDate, $toDate])
                    ->count(),
                'average_order_value' => Order::where('status', 'paid')
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->avg('grand_total'),
            ],
            'sales' => $this->getSalesReport($fromDate, $toDate),
            'products' => $this->getProductsReport($fromDate, $toDate),
            'users' => $this->getUserReport($fromDate, $toDate),
            'payments' => $this->getPaymentReport($fromDate, $toDate),
        ];
    }
}

