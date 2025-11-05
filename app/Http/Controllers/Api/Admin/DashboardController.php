<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Services\InventoryAlertService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected AnalyticsService $analyticsService;
    protected InventoryAlertService $inventoryAlertService;

    public function __construct(
        AnalyticsService $analyticsService,
        InventoryAlertService $inventoryAlertService
    ) {
        $this->analyticsService = $analyticsService;
        $this->inventoryAlertService = $inventoryAlertService;
    }

    /**
     * دریافت آمار کلی داشبورد
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', '30days');

        $stats = $this->analyticsService->getDashboardStats($period);

        return response()->json([
            'stats' => $stats,
            'period' => $period,
        ], 200);
    }

    /**
     * دریافت نمودار فروش
     */
    public function salesChart(Request $request)
    {
        $period = $request->get('period', '30days');
        $groupBy = $request->get('group_by', 'day');

        $chart = $this->analyticsService->getSalesChart($period, $groupBy);

        return response()->json([
            'chart' => $chart,
            'period' => $period,
            'group_by' => $groupBy,
        ], 200);
    }

    /**
     * دریافت محصولات پرفروش
     */
    public function topProducts(Request $request)
    {
        $limit = $request->get('limit', 10);
        $period = $request->get('period', '30days');

        $products = $this->analyticsService->getTopSellingProducts($limit, $period);

        return response()->json([
            'products' => $products,
        ], 200);
    }

    /**
     * دریافت نمودار کاربران
     */
    public function usersChart(Request $request)
    {
        $period = $request->get('period', '30days');

        $chart = $this->analyticsService->getUsersChart($period);

        return response()->json([
            'chart' => $chart,
            'period' => $period,
        ], 200);
    }

    /**
     * دریافت آمار دسته‌بندی‌ها
     */
    public function categoryStats(Request $request)
    {
        $period = $request->get('period', '30days');

        $stats = $this->analyticsService->getCategoryStats($period);

        return response()->json([
            'categories' => $stats,
            'period' => $period,
        ], 200);
    }

    /**
     * دریافت محصولات با موجودی کم
     */
    public function lowStock(Request $request)
    {
        $threshold = $request->get('threshold');

        $products = $this->analyticsService->getLowStockProducts($threshold);

        return response()->json([
            'products' => $products,
            'count' => $products->count(),
        ], 200);
    }

    /**
     * بررسی و ارسال هشدارهای موجودی
     */
    public function checkInventoryAlerts(Request $request)
    {
        $lowStockCount = $this->inventoryAlertService->checkLowStock();
        $outOfStockCount = $this->inventoryAlertService->checkOutOfStock();

        return response()->json([
            'message' => 'Inventory alerts checked.',
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ], 200);
    }
}

