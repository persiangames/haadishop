<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyRedemption extends Model
{
    protected $table = 'loyalty_redemptions';

    protected $fillable = [
        'user_id',
        'points_spent',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'points_spent' => 'bigInteger',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

