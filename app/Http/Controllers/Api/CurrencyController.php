<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * دریافت لیست ارزهای فعال
     */
    public function index()
    {
        $currencies = $this->currencyService->getActiveCurrencies();

        return response()->json([
            'currencies' => $currencies,
            'default' => $this->currencyService->getDefaultCurrency(),
        ], 200);
    }

    /**
     * تبدیل مبلغ
     */
    public function convert(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'string', 'size:3'],
            'to' => ['required', 'string', 'size:3'],
        ]);

        $converted = $this->currencyService->convert(
            $request->amount,
            $request->from,
            $request->to
        );

        $rate = $this->currencyService->getExchangeRate(
            $request->from,
            $request->to
        );

        return response()->json([
            'amount' => $request->amount,
            'from' => $request->from,
            'to' => $request->to,
            'rate' => $rate,
            'converted' => round($converted, 2),
        ], 200);
    }

    /**
     * دریافت نرخ ارز
     */
    public function rate(Request $request)
    {
        $request->validate([
            'from' => ['required', 'string', 'size:3'],
            'to' => ['required', 'string', 'size:3'],
        ]);

        $rate = $this->currencyService->getExchangeRate(
            $request->from,
            $request->to
        );

        return response()->json([
            'from' => $request->from,
            'to' => $request->to,
            'rate' => $rate,
        ], 200);
    }
}

