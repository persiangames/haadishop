<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lottery;
use App\Models\Order;
use App\Services\LotteryService;
use App\Services\AffiliateService;
use Illuminate\Http\Request;

class LotteryController extends Controller
{
    protected LotteryService $lotteryService;
    protected AffiliateService $affiliateService;

    public function __construct(LotteryService $lotteryService, AffiliateService $affiliateService)
    {
        $this->lotteryService = $lotteryService;
        $this->affiliateService = $affiliateService;
    }

    public function show(Request $request, $productSlug)
    {
        $product = \App\Models\Product::where('slug', $productSlug)->firstOrFail();
        
        $lottery = Lottery::where('product_id', $product->id)
            ->where('is_active', true)
            ->first();

        if (!$lottery) {
            return response()->json([
                'message' => 'No active lottery for this product.',
            ], 404);
        }

        $stats = $this->lotteryService->getLotteryStats($lottery->id);

        return response()->json([
            'lottery' => $stats,
        ], 200);
    }

    public function createEntry(Request $request, $orderId)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->findOrFail($orderId);

        if ($order->status !== 'paid') {
            return response()->json([
                'message' => 'Order must be paid before creating lottery entry.',
            ], 422);
        }

        $request->validate([
            'lottery_id' => ['required', 'exists:lotteries,id'],
            'affiliate_code' => ['nullable', 'string'],
        ]);

        try {
            // ایجاد referral اگر affiliate_code وجود دارد
            if ($request->affiliate_code) {
                $this->affiliateService->createReferral($order, $request->affiliate_code);
            }

            // ایجاد lottery entry
            $entry = $this->lotteryService->createLotteryEntry(
                $order,
                $request->lottery_id,
                $request->affiliate_code
            );

            return response()->json([
                'message' => 'Lottery entry created successfully.',
                'entry' => [
                    'id' => $entry->id,
                    'lottery_code' => $entry->lottery_code,
                    'weight' => $entry->weight,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function draw(Request $request, $lotteryId)
    {
        // فقط ادمین می‌تواند قرعه‌کشی را دستی انجام دهد
        $request->validate([
            'lottery_id' => ['required', 'exists:lotteries,id'],
        ]);

        try {
            $result = $this->lotteryService->drawLottery($lotteryId);

            return response()->json([
                'message' => 'Lottery drawn successfully.',
                'draw' => [
                    'id' => $result['draw']->id,
                    'draw_number' => $result['draw']->draw_number,
                    'winner' => [
                        'user_id' => $result['user']->id,
                        'name' => $result['user']->name,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function stats(Request $request, $lotteryId)
    {
        $stats = $this->lotteryService->getLotteryStats($lotteryId);

        return response()->json([
            'lottery' => $stats,
        ], 200);
    }

    public function myEntries(Request $request)
    {
        $user = $request->user();

        $entries = \App\Models\LotteryEntry::where('buyer_user_id', $user->id)
            ->with(['lottery.product', 'winner'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'entries' => $entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'lottery_code' => $entry->lottery_code,
                    'weight' => $entry->weight,
                    'lottery' => [
                        'id' => $entry->lottery->id,
                        'product' => [
                            'id' => $entry->lottery->product->id,
                            'slug' => $entry->lottery->product->slug,
                            'title' => $entry->lottery->product->translation(app()->getLocale())->title ?? null,
                        ],
                        'completion_percent' => $entry->lottery->completion_percent,
                    ],
                    'is_winner' => $entry->winner !== null,
                    'won_at' => $entry->winner ? $entry->winner->draw->drawn_at : null,
                ];
            }),
        ], 200);
    }
}

