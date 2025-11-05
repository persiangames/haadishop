<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportingService $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * گزارش کامل
     */
    public function fullReport(Request $request)
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $report = $this->reportingService->getFullReport(
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'report' => $report,
        ], 200);
    }

    /**
     * گزارش فروش
     */
    public function salesReport(Request $request)
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'group_by' => ['sometimes', 'in:hour,day,week,month'],
        ]);

        $report = $this->reportingService->getSalesReport(
            $request->from_date,
            $request->to_date,
            $request->get('group_by', 'day')
        );

        return response()->json([
            'report' => $report,
        ], 200);
    }

    /**
     * گزارش محصولات
     */
    public function productsReport(Request $request)
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $report = $this->reportingService->getProductsReport(
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'report' => $report,
        ], 200);
    }

    /**
     * گزارش کاربران
     */
    public function usersReport(Request $request)
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $report = $this->reportingService->getUserReport(
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'report' => $report,
        ], 200);
    }

    /**
     * گزارش پرداخت‌ها
     */
    public function paymentsReport(Request $request)
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $report = $this->reportingService->getPaymentReport(
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'report' => $report,
        ], 200);
    }
}

