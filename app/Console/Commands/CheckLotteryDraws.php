<?php

namespace App\Console\Commands;

use App\Models\Lottery;
use App\Services\LotteryService;
use Illuminate\Console\Command;

class CheckLotteryDraws extends Command
{
    protected $signature = 'lottery:check-draws';
    protected $description = 'Check and auto-draw lotteries that reached threshold';

    protected LotteryService $lotteryService;

    public function __construct(LotteryService $lotteryService)
    {
        parent::__construct();
        $this->lotteryService = $lotteryService;
    }

    public function handle()
    {
        $lotteries = Lottery::where('is_active', true)
            ->get()
            ->filter(function ($lottery) {
                return $lottery->shouldAutoDraw();
            });

        foreach ($lotteries as $lottery) {
            try {
                $this->info("Drawing lottery ID: {$lottery->id}");
                $result = $this->lotteryService->drawLottery($lottery->id);
                $this->info("Winner: {$result['user']->name} (ID: {$result['user']->id})");
            } catch (\Exception $e) {
                $this->error("Failed to draw lottery {$lottery->id}: {$e->getMessage()}");
            }
        }

        $this->info("Checked " . $lotteries->count() . " lotteries.");
    }
}

