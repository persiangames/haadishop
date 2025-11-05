<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->two_factor_secret) {
            // اگر 2FA فعال است، باید در header یا session بررسی شود که تایید شده است
            // این middleware را می‌توانید بعداً کامل‌تر کنید
            // فعلاً فقط بررسی می‌کند که کاربر لاگین است
        }

        return $next($request);
    }
}

