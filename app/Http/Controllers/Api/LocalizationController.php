<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalizationService;
use Illuminate\Http\Request;

class LocalizationController extends Controller
{
    protected LocalizationService $localizationService;

    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * تغییر زبان
     */
    public function setLocale(Request $request)
    {
        $request->validate([
            'locale' => ['required', 'string', 'in:fa,en'],
        ]);

        $success = $this->localizationService->setLocale($request->locale);

        if ($success) {
            return response()->json([
                'message' => 'Locale changed successfully.',
                'locale' => $request->locale,
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid locale.',
        ], 422);
    }

    /**
     * دریافت زبان فعلی
     */
    public function getLocale()
    {
        return response()->json([
            'locale' => $this->localizationService->getCurrentLocale(),
            'available_locales' => $this->localizationService->getAvailableLocales(),
            'default_locale' => $this->localizationService->getDefaultLocale(),
        ], 200);
    }
}

