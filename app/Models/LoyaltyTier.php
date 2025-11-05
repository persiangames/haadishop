<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    protected $table = 'loyalty_tiers';

    protected $fillable = [
        'code',
        'min_points',
        'benefits',
    ];

    protected function casts(): array
    {
        return [
            'min_points' => 'bigInteger',
            'benefits' => 'array',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'loyalty_tier_id');
    }
}

