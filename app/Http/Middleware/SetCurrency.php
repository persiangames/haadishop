<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\CurrencyService;

class SetCurrency
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // بررسی ارز از request
        $currency = $request->get('currency')
            ?? session('currency')
            ?? $this->currencyService->getDefaultCurrency()->code;

        // بررسی اینکه ارز فعال است
        $activeCurrencies = $this->currencyService->getActiveCurrencies()
            ->pluck('code')
            ->toArray();

        if (!in_array($currency, $activeCurrencies)) {
            $currency = $this->currencyService->getDefaultCurrency()->code;
        }

        session(['currency' => $currency]);
        app()->instance('currency', $currency);

        return $next($request);
    }
}

