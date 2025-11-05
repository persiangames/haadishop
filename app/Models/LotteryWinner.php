<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryWinner extends Model
{
    protected $table = 'lottery_winners';

    protected $fillable = [
        'lottery_draw_id',
        'lottery_entry_id',
        'user_id',
        'is_claimed',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_claimed' => 'boolean',
            'claimed_at' => 'datetime',
        ];
    }

    public function draw()
    {
        return $this->belongsTo(LotteryDraw::class);
    }

    public function entry()
    {
        return $this->belongsTo(LotteryEntry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

