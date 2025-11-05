<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Payment;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    /**
     * دریافت آمار کلی فروشگاه
     */
    public function getDashboardStats($period = '30days')
    {
        $cacheKey = "analytics:dashboard:{$period}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($period) {
            $dateRange = $this->getDateRange($period);

            $stats = [
                'total_revenue' => $this->getTotalRevenue($dateRange),
                'total_orders' => $this->getTotalOrders($dateRange),
                'total_users' => $this->getTotalUsers($dateRange),
                'total_products' => Product::where('is_published', true)->count(),
                'average_order_value' => $this->getAverageOrderValue($dateRange),
                'conversion_rate' => $this->getConversionRate($dateRange),
                'new_users' => $this->getNewUsers($dateRange),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'low_stock_products' => $this->getLowStockProductsCount(),
            ];

            return $stats;
        });
    }

    /**
     * دریافت آمار فروش بر اساس دوره زمانی
     */
    public function getSalesChart($period = '30days', $groupBy = 'day')
    {
        $cacheKey = "analytics:sales-chart:{$period}:{$groupBy}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($period, $groupBy) {
            $dateRange = $this->getDateRange($period);
            
            $format = match($groupBy) {
                'hour' => '%Y-%m-%d %H:00:00',
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                default => '%Y-%m-%d',
            };

            $sales = Order::where('status', 'paid')
                ->whereBetween('created_at', $dateRange)
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                    DB::raw('SUM(grand_total) as revenue'),
                    DB::raw('COUNT(*) as orders')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            return $sales;
        });
    }

    /**
     * دریافت محصولات پرفروش
     */
    public function getTopSellingProducts($limit = 10, $period = '30days')
    {
        $cacheKey = "analytics:top-products:{$limit}:{$period}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($limit, $period) {
            $dateRange = $this->getDateRange($period);

            return OrderItem::whereHas('order', function ($q) use ($dateRange) {
                $q->where('status', 'paid')
                  ->whereBetween('created_at', $dateRange);
            })
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.slug',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.line_total) as total_revenue')
            )
            ->groupBy('products.id', 'products.slug')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();
        });
    }

    /**
     * دریافت آمار کاربران
     */
    public function getUsersChart($period = '30days')
    {
        $cacheKey = "analytics:users-chart:{$period}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($period) {
            $dateRange = $this->getDateRange($period);

            return User::whereBetween('created_at', $dateRange)
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });
    }

    /**
     * دریافت آمار دسته‌بندی‌ها
     */
    public function getCategoryStats($period = '30days')
    {
        $cacheKey = "analytics:categories:{$period}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($period) {
            $dateRange = $this->getDateRange($period);

            return DB::table('order_items')
                ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->join('product_categories', 'products.id', '=', 'product_categories.product_id')
                ->join('categories', 'product_categories.category_id', '=', 'categories.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'paid')
                ->whereBetween('orders.created_at', $dateRange)
                ->select(
                    'categories.id',
                    'categories.slug',
                    DB::raw('SUM(order_items.quantity) as total_sold'),
                    DB::raw('SUM(order_items.line_total) as total_revenue')
                )
                ->groupBy('categories.id', 'categories.slug')
                ->orderBy('total_revenue', 'desc')
                ->get();
        });
    }

    /**
     * دریافت محصولات با موجودی کم
     */
    public function getLowStockProducts($threshold = null)
    {
        $threshold = $threshold ?? config('inventory.low_stock_threshold', 10);

        return DB::table('inventories')
            ->join('product_variants', 'inventories.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('products.is_published', true)
            ->whereRaw('(inventories.quantity_on_hand - inventories.quantity_reserved) <= inventories.low_stock_threshold')
            ->select(
                'products.id',
                'products.slug',
                'product_variants.sku',
                'inventories.quantity_on_hand',
                'inventories.quantity_reserved',
                DB::raw('(inventories.quantity_on_hand - inventories.quantity_reserved) as available')
            )
            ->get();
    }

    protected function getTotalRevenue($dateRange)
    {
        return Order::where('status', 'paid')
            ->whereBetween('created_at', $dateRange)
            ->sum('grand_total');
    }

    protected function getTotalOrders($dateRange)
    {
        return Order::whereBetween('created_at', $dateRange)
            ->count();
    }

    protected function getTotalUsers($dateRange = null)
    {
        if ($dateRange) {
            return User::whereBetween('created_at', $dateRange)->count();
        }
        return User::count();
    }

    protected function getAverageOrderValue($dateRange)
    {
        $avg = Order::where('status', 'paid')
            ->whereBetween('created_at', $dateRange)
            ->avg('grand_total');
        
        return round($avg ?? 0, 2);
    }

    protected function getConversionRate($dateRange)
    {
        $carts = Cart::whereBetween('created_at', $dateRange)
            ->whereHas('items')
            ->count();

        $orders = Order::whereBetween('created_at', $dateRange)->count();

        if ($carts == 0) {
            return 0;
        }

        return round(($orders / $carts) * 100, 2);
    }

    protected function getNewUsers($dateRange)
    {
        return User::whereBetween('created_at', $dateRange)->count();
    }

    protected function getLowStockProductsCount()
    {
        return DB::table('inventories')
            ->whereRaw('(quantity_on_hand - quantity_reserved) <= low_stock_threshold')
            ->count();
    }

    protected function getDateRange($period)
    {
        return match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7days' => [now()->subDays(7), now()],
            '30days' => [now()->subDays(30), now()],
            '90days' => [now()->subDays(90), now()],
            '1year' => [now()->subYear(), now()],
            default => [now()->subDays(30), now()],
        };
    }
}

