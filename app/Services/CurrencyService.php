<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    /**
     * دریافت نرخ ارز
     */
    public function getExchangeRate($baseCurrency, $quoteCurrency)
    {
        if ($baseCurrency === $quoteCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate:{$baseCurrency}:{$quoteCurrency}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($baseCurrency, $quoteCurrency) {
            $rate = ExchangeRate::where('base_currency', $baseCurrency)
                ->where('quote_currency', $quoteCurrency)
                ->orderBy('fetched_at', 'desc')
                ->first();

            if ($rate && $rate->fetched_at->isAfter(now()->subHours(24))) {
                return $rate->rate;
            }

            // دریافت نرخ جدید از API
            return $this->fetchExchangeRate($baseCurrency, $quoteCurrency);
        });
    }

    /**
     * تبدیل مبلغ
     */
    public function convert($amount, $fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return $amount * $rate;
    }

    /**
     * دریافت نرخ ارز از API
     */
    protected function fetchExchangeRate($baseCurrency, $quoteCurrency)
    {
        // استفاده از API آزاد (مثل exchangerate-api.com یا fixer.io)
        // TODO: پیاده‌سازی با API واقعی
        
        // برای مثال، از یک API آزاد استفاده می‌کنیم
        try {
            $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$baseCurrency}");
            $data = $response->json();

            if (isset($data['rates'][$quoteCurrency])) {
                $rate = $data['rates'][$quoteCurrency];

                // ذخیره در دیتابیس
                ExchangeRate::create([
                    'base_currency' => $baseCurrency,
                    'quote_currency' => $quoteCurrency,
                    'rate' => $rate,
                    'fetched_at' => now(),
                    'provider' => 'exchangerate-api',
                ]);

                return $rate;
            }
        } catch (\Exception $e) {
            // در صورت خطا، از آخرین نرخ موجود استفاده می‌کنیم
            $lastRate = ExchangeRate::where('base_currency', $baseCurrency)
                ->where('quote_currency', $quoteCurrency)
                ->orderBy('fetched_at', 'desc')
                ->first();

            return $lastRate ? $lastRate->rate : 1.0;
        }

        return 1.0;
    }

    /**
     * دریافت ارز پیش‌فرض
     */
    public function getDefaultCurrency()
    {
        return Cache::remember('default_currency', now()->addDay(), function () {
            return Currency::where('is_default', true)->first();
        });
    }

    /**
     * دریافت لیست ارزهای فعال
     */
    public function getActiveCurrencies()
    {
        return Cache::remember('active_currencies', now()->addDay(), function () {
            return Currency::where('is_active', true)->get();
        });
    }

    /**
     * به‌روزرسانی نرخ‌های ارز
     */
    public function updateExchangeRates()
    {
        $currencies = Currency::where('is_active', true)->pluck('code');
        $defaultCurrency = $this->getDefaultCurrency()->code;

        foreach ($currencies as $currency) {
            if ($currency !== $defaultCurrency) {
                $this->fetchExchangeRate($defaultCurrency, $currency);
            }
        }

        Cache::forget('exchange_rate:*');
    }
}

