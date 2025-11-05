<?php

namespace App\Services;

use App\Models\Lottery;
use App\Models\LotteryEntry;
use App\Models\LotteryDraw;
use App\Models\LotteryWinner;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LotteryService
{
    public function createLotteryEntry($order, $lotteryId, $affiliateCode = null)
    {
        $lottery = Lottery::findOrFail($lotteryId);

        // بررسی اینکه آیا محصول سفارش با محصول قرعه‌کشی یکسان است
        if ($lottery->product_id !== $order->items->first()->variant->product_id) {
            throw new \Exception('Order product does not match lottery product.');
        }

        // محاسبه weight بر اساس تعداد خریدهای قبلی از طریق لینک معرفی
        $weight = $this->calculateWeight($order->user_id, $affiliateCode, $lotteryId);

        $entry = LotteryEntry::create([
            'lottery_id' => $lotteryId,
            'order_id' => $order->id,
            'buyer_user_id' => $order->user_id,
            'affiliate_user_id' => $affiliateCode ? User::where('affiliate_code', $affiliateCode)->value('id') : null,
            'lottery_code' => $this->generateLotteryCode(),
            'weight' => $weight,
        ]);

        // به‌روزرسانی مبلغ صندوق قرعه‌کشی
        $lottery->increment('current_pool_amount', $order->grand_total);

        // بررسی اینکه آیا باید قرعه‌کشی خودکار انجام شود
        if ($lottery->shouldAutoDraw()) {
            dispatch(new \App\Jobs\AutoDrawLottery($lottery->id));
        }

        return $entry;
    }

    protected function calculateWeight($userId, $affiliateCode = null, $lotteryId = null)
    {
        $baseWeight = 1;

        if ($affiliateCode) {
            // تعداد خریدهای قبلی از طریق لینک معرفی
            $affiliateUserId = User::where('affiliate_code', $affiliateCode)->value('id');
            
            if ($affiliateUserId) {
                $previousPurchases = Order::where('user_id', $userId)
                    ->whereHas('affiliateReferrals', function ($q) use ($affiliateUserId) {
                        $q->where('affiliate_user_id', $affiliateUserId);
                    })
                    ->count();

                // افزایش weight بر اساس تعداد خریدها
                // هر خرید قبلی = +1 weight
                $baseWeight += $previousPurchases;
            }
        }

        // اگر lottery_id داده شده، تعداد خریدهای مستقیم از این محصول را محاسبه می‌کنیم
        if ($lotteryId) {
            $lottery = Lottery::find($lotteryId);
            if ($lottery) {
                $directPurchases = Order::where('user_id', $userId)
                    ->whereDoesntHave('affiliateReferrals')
                    ->whereHas('items.variant.product', function ($q) use ($lottery) {
                        $q->where('products.id', $lottery->product_id);
                    })
                    ->count();

                $baseWeight += ($directPurchases * 0.5); // خرید مستقیم هم weight کمتری دارد
            }
        }

        return $baseWeight;
    }

    public function drawLottery($lotteryId)
    {
        $lottery = Lottery::findOrFail($lotteryId);

        if (!$lottery->is_active) {
            throw new \Exception('Lottery is not active.');
        }

        if ($lottery->current_pool_amount < $lottery->target_pool_amount) {
            throw new \Exception('Lottery pool has not reached target amount.');
        }

        return DB::transaction(function () use ($lottery) {
            // شماره قرعه‌کشی جدید
            $lastDraw = LotteryDraw::where('lottery_id', $lottery->id)
                ->orderBy('draw_number', 'desc')
                ->first();

            $drawNumber = $lastDraw ? $lastDraw->draw_number + 1 : 1;

            // ایجاد قرعه‌کشی جدید
            $draw = LotteryDraw::create([
                'lottery_id' => $lottery->id,
                'draw_number' => $drawNumber,
                'status' => 'scheduled',
                'drawn_at' => now(),
            ]);

            // دریافت لیست برندگان قبلی (برای حذف از قرعه‌کشی)
            $previousWinners = LotteryWinner::whereHas('draw', function ($q) use ($lottery) {
                $q->where('lottery_id', $lottery->id)
                  ->where('draw_number', '<', $drawNumber);
            })->pluck('user_id')->toArray();

            // دریافت ورودی‌های واجد شرایط (غیر برنده در قرعه‌کشی‌های قبلی)
            $eligibleEntries = LotteryEntry::where('lottery_id', $lottery->id)
                ->whereNotIn('buyer_user_id', $previousWinners)
                ->get();

            if ($eligibleEntries->isEmpty()) {
                $draw->update(['status' => 'cancelled']);
                throw new \Exception('No eligible entries for lottery draw.');
            }

            // انتخاب برنده بر اساس weight
            $winner = $this->selectWinner($eligibleEntries);

            // ثبت برنده
            $lotteryWinner = LotteryWinner::create([
                'lottery_draw_id' => $draw->id,
                'lottery_entry_id' => $winner->id,
                'user_id' => $winner->buyer_user_id,
                'is_claimed' => false,
            ]);

            $draw->update(['status' => 'completed']);

            // ریست کردن صندوق قرعه‌کشی برای قرعه‌کشی بعدی
            $lottery->update([
                'current_pool_amount' => 0,
            ]);

            return [
                'draw' => $draw,
                'winner' => $lotteryWinner,
                'user' => $winner->buyer,
            ];
        });
    }

    protected function selectWinner($entries)
    {
        // محاسبه total weight
        $totalWeight = $entries->sum('weight');

        // انتخاب عدد تصادفی
        $random = mt_rand(1, $totalWeight);

        // پیدا کردن برنده بر اساس weight
        $currentWeight = 0;
        foreach ($entries as $entry) {
            $currentWeight += $entry->weight;
            if ($random <= $currentWeight) {
                return $entry;
            }
        }

        // در صورت خطا، اولین entry را برمی‌گرداند
        return $entries->first();
    }

    public function generateLotteryCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
        } while (LotteryEntry::where('lottery_code', $code)->exists());

        return $code;
    }

    public function createLottery($productId, $targetAmount, $currencyCode = 'IRR')
    {
        $lottery = Lottery::create([
            'product_id' => $productId,
            'target_pool_amount' => $targetAmount,
            'current_pool_amount' => 0,
            'currency_code' => $currencyCode,
            'is_active' => true,
            'auto_draw_threshold_percent' => 100,
        ]);

        return $lottery;
    }

    public function getLotteryStats($lotteryId)
    {
        $lottery = Lottery::with(['product', 'entries', 'draws.winners'])->findOrFail($lotteryId);

        return [
            'id' => $lottery->id,
            'product' => [
                'id' => $lottery->product->id,
                'slug' => $lottery->product->slug,
                'title' => $lottery->product->translation(app()->getLocale())->title ?? null,
            ],
            'target_pool_amount' => $lottery->target_pool_amount,
            'current_pool_amount' => $lottery->current_pool_amount,
            'completion_percent' => $lottery->completion_percent,
            'currency_code' => $lottery->currency_code,
            'is_active' => $lottery->is_active,
            'total_entries' => $lottery->entries->count(),
            'total_draws' => $lottery->draws->count(),
            'winners' => $lottery->draws->flatMap(function ($draw) {
                return $draw->winners->map(function ($winner) {
                    return [
                        'draw_number' => $draw->draw_number,
                        'user' => [
                            'id' => $winner->user->id,
                            'name' => $winner->user->name,
                        ],
                        'is_claimed' => $winner->is_claimed,
                        'claimed_at' => $winner->claimed_at,
                    ];
                });
            }),
        ];
    }
}

