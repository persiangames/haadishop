<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AffiliateService;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    protected AffiliateService $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = $this->affiliateService->getAffiliateStats($user->id);

        return response()->json([
            'stats' => $stats,
        ], 200);
    }

    public function generateShareLink(Request $request, $productSlug)
    {
        $user = $request->user();
        $lotteryCode = $request->get('lottery_code');

        $link = $this->affiliateService->generateShareLink(
            $productSlug,
            $user->affiliate_code,
            $lotteryCode
        );

        return response()->json([
            'share_link' => $link,
            'affiliate_code' => $user->affiliate_code,
        ], 200);
    }

    public function trackClick(Request $request)
    {
        $request->validate([
            'ref' => ['required', 'string'],
            'product_id' => ['nullable', 'exists:products,id'],
        ]);

        $click = $this->affiliateService->trackClick(
            $request->ref,
            $request->product_id,
            $request
        );

        if ($click) {
            return response()->json([
                'message' => 'Click tracked successfully.',
                'click_id' => $click->id,
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid affiliate code.',
        ], 422);
    }
}

