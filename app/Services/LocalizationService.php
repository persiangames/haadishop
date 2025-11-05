<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocalizationService
{
    /**
     * تغییر زبان
     */
    public function setLocale($locale)
    {
        if (in_array($locale, $this->getAvailableLocales())) {
            App::setLocale($locale);
            Session::put('locale', $locale);
            return true;
        }

        return false;
    }

    /**
     * دریافت زبان فعلی
     */
    public function getCurrentLocale()
    {
        return Session::get('locale', App::getLocale());
    }

    /**
     * دریافت زبان‌های موجود
     */
    public function getAvailableLocales()
    {
        return ['fa', 'en'];
    }

    /**
     * دریافت زبان پیش‌فرض
     */
    public function getDefaultLocale()
    {
        return config('app.locale', 'fa');
    }

    /**
     * ترجمه متن
     */
    public function translate($key, $replace = [], $locale = null)
    {
        $locale = $locale ?? $this->getCurrentLocale();
        return trans($key, $replace, $locale);
    }
}

