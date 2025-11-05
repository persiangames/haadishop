<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // بررسی قرعه‌کشی‌ها هر 5 دقیقه
        $schedule->command('lottery:check-draws')->everyFiveMinutes();
        
        // بررسی موجودی هر ساعت
        $schedule->command('inventory:check-alerts')->hourly();
        
        // به‌روزرسانی نرخ‌های ارز هر 6 ساعت
        $schedule->command('currency:update-rates')->everySixHours();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}

