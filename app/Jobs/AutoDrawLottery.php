<?php

namespace App\Jobs;

use App\Services\LotteryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoDrawLottery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $lotteryId;

    public function __construct(int $lotteryId)
    {
        $this->lotteryId = $lotteryId;
    }

    public function handle(LotteryService $lotteryService)
    {
        try {
            $result = $lotteryService->drawLottery($this->lotteryId);

            Log::info('Lottery drawn automatically', [
                'lottery_id' => $this->lotteryId,
                'draw_id' => $result['draw']->id,
                'winner_user_id' => $result['user']->id,
            ]);

            // TODO: ارسال اعلان به برنده
        } catch (\Exception $e) {
            Log::error('Failed to auto-draw lottery', [
                'lottery_id' => $this->lotteryId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

