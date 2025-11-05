<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        // تعیین اینکه ورود با email است یا phone
        $field = $request->email ? 'email' : 'phone';
        $value = $request->email ?? $request->phone;

        if (empty($value)) {
            throw ValidationException::withMessages([
                'credentials' => ['Either email or phone must be provided.'],
            ]);
        }

        $user = User::where($field, $value)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'credentials' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'account' => ['Your account is not active.'],
            ]);
        }

        // به‌روزرسانی آخرین زمان ورود
        $user->update(['last_login_at' => now()]);

        // بررسی 2FA
        if ($user->two_factor_secret) {
            return response()->json([
                'requires_2fa' => true,
                'user_id' => $user->id,
                'message' => 'Two-factor authentication required.',
            ], 200);
        }

        // ورود موفق - ایجاد توکن
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ], 200);
    }
}

