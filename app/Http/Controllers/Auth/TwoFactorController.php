<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    public function verify(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->two_factor_secret) {
            throw ValidationException::withMessages([
                'code' => ['Two-factor authentication is not enabled for this account.'],
            ]);
        }

        $valid = $this->twoFactorService->verifyCode($user, $request->code);

        if (!$valid) {
            // بررسی recovery codes
            $valid = $this->twoFactorService->verifyRecoveryCode($user, $request->code);
        }

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => ['Invalid two-factor authentication code.'],
            ]);
        }

        // ورود موفق با 2FA
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Two-factor authentication verified.',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->two_factor_secret) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled.',
            ], 400);
        }

        $secretKey = $this->twoFactorService->generateSecret();
        $qrCodeUrl = $this->twoFactorService->generateQRCode($secretKey, $user->email ?? $user->phone);

        // ذخیره موقت secret (هنوز فعال نشده)
        return response()->json([
            'secret' => $secretKey,
            'qr_code_svg' => $qrCodeUrl,
            'message' => 'Scan this QR code with your authenticator app.',
        ], 200);
    }

    public function enable(Request $request)
    {
        $request->validate([
            'secret' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        // برای enable باید از secret جدید استفاده کنیم
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $valid = $google2fa->verifyKey($request->secret, $request->code);

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        // تولید recovery codes
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => $request->secret,
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully.',
            'recovery_codes' => $recoveryCodes,
            'warning' => 'Save these recovery codes in a safe place. You will need them if you lose access to your authenticator device.',
        ], 200);
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Invalid password.'],
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return response()->json([
            'message' => 'Two-factor authentication disabled successfully.',
        ], 200);
    }

    public function showRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], 400);
        }

        return response()->json([
            'recovery_codes' => $user->two_factor_recovery_codes ?? [],
        ], 200);
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Invalid password.'],
            ]);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        return response()->json([
            'message' => 'Recovery codes regenerated successfully.',
            'recovery_codes' => $recoveryCodes,
        ], 200);
    }

}

