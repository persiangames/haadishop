<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LocalizationService;

class SetLocale
{
    protected LocalizationService $localizationService;

    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // بررسی زبان از request header
        $locale = $request->header('Accept-Language') 
            ?? $request->get('locale')
            ?? session('locale')
            ?? $this->localizationService->getDefaultLocale();

        // محدود کردن به زبان‌های موجود
        if (!in_array($locale, $this->localizationService->getAvailableLocales())) {
            $locale = $this->localizationService->getDefaultLocale();
        }

        $this->localizationService->setLocale($locale);

        return $next($request);
    }
}

