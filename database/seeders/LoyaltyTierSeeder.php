<?php

namespace Database\Seeders;

use App\Models\LoyaltyTier;
use Illuminate\Database\Seeder;

class LoyaltyTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'code' => 'bronze',
                'min_points' => 0,
                'benefits' => ['discount_5_percent'],
            ],
            [
                'code' => 'silver',
                'min_points' => 1000,
                'benefits' => ['discount_10_percent', 'free_shipping'],
            ],
            [
                'code' => 'gold',
                'min_points' => 5000,
                'benefits' => ['discount_15_percent', 'free_shipping', 'priority_support'],
            ],
            [
                'code' => 'platinum',
                'min_points' => 20000,
                'benefits' => ['discount_20_percent', 'free_shipping', 'priority_support', 'exclusive_products'],
            ],
        ];

        foreach ($tiers as $tier) {
            LoyaltyTier::updateOrCreate(
                ['code' => $tier['code']],
                $tier
            );
        }
    }
}

