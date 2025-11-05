<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\AffiliateClick;
use App\Models\AffiliateReferral;
use Illuminate\Support\Facades\DB;

class AffiliateService
{
    public function trackClick($affiliateCode, $productId = null, $request)
    {
        $affiliate = User::where('affiliate_code', $affiliateCode)->first();

        if (!$affiliate) {
            return null;
        }

        $click = AffiliateClick::create([
            'affiliate_user_id' => $affiliate->id,
            'product_id' => $productId,
            'ref_code' => $affiliateCode,
            'landing_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $click;
    }

    public function createReferral($order, $affiliateCode = null)
    {
        if (!$affiliateCode) {
            // بررسی اینکه آیا کاربر از طریق لینک معرفی آمده است
            $sessionId = session()->getId();
            $click = AffiliateClick::where('ip', request()->ip())
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($click) {
                $affiliateCode = $click->ref_code;
            }
        }

        if (!$affiliateCode) {
            return null;
        }

        $affiliate = User::where('affiliate_code', $affiliateCode)->first();

        if (!$affiliate || $affiliate->id === $order->user_id) {
            return null; // نمی‌تواند خودش را معرفی کند
        }

        // محاسبه کمیسیون (مثلاً 5% از مبلغ سفارش)
        $commissionRate = config('affiliate.commission_rate', 0.05);
        $commissionAmount = $order->grand_total * $commissionRate;

        $referral = AffiliateReferral::create([
            'affiliate_user_id' => $affiliate->id,
            'referred_user_id' => $order->user_id,
            'order_id' => $order->id,
            'commission_amount' => $commissionAmount,
            'commission_currency' => $order->currency_code,
            'status' => 'pending',
        ]);

        return $referral;
    }

    public function getAffiliateStats($userId)
    {
        $user = User::findOrFail($userId);

        $clicks = AffiliateClick::where('affiliate_user_id', $userId)->count();
        $referrals = AffiliateReferral::where('affiliate_user_id', $userId)->count();
        $approvedReferrals = AffiliateReferral::where('affiliate_user_id', $userId)
            ->where('status', 'approved')
            ->count();
        $totalCommission = AffiliateReferral::where('affiliate_user_id', $userId)
            ->where('status', 'approved')
            ->sum('commission_amount');
        $pendingCommission = AffiliateReferral::where('affiliate_user_id', $userId)
            ->where('status', 'pending')
            ->sum('commission_amount');

        return [
            'affiliate_code' => $user->affiliate_code,
            'affiliate_link' => config('app.url') . '/?ref=' . $user->affiliate_code,
            'total_clicks' => $clicks,
            'total_referrals' => $referrals,
            'approved_referrals' => $approvedReferrals,
            'total_commission' => $totalCommission,
            'pending_commission' => $pendingCommission,
        ];
    }

    public function generateShareLink($productSlug, $affiliateCode, $lotteryCode = null)
    {
        $baseUrl = config('app.url');
        $link = $baseUrl . '/product/' . $productSlug . '?ref=' . $affiliateCode;

        if ($lotteryCode) {
            $link .= '&lottery=' . $lotteryCode;
        }

        return $link;
    }
}

