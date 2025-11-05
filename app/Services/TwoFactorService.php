<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        return $this->google2fa->verifyKey($user->two_factor_secret, $code);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        
        if (!in_array($code, $recoveryCodes)) {
            return false;
        }

        // حذف recovery code استفاده شده
        $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
        $user->update(['two_factor_recovery_codes' => $recoveryCodes]);

        return true;
    }

    public function generateQRCode(string $secret, string $emailOrPhone): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $emailOrPhone,
            $secret
        );

        $writer = new Writer(
            new ImageRenderer(
                new SvgImageBackEnd(),
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400)
            )
        );

        return base64_encode($writer->writeString($qrCodeUrl));
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8) . '-' . substr(md5(uniqid(rand(), true)), 0, 8));
        }
        return $codes;
    }
}

