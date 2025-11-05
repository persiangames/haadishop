<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryDraw extends Model
{
    protected $table = 'lottery_draws';

    protected $fillable = [
        'lottery_id',
        'draw_number',
        'drawn_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'draw_number' => 'integer',
            'drawn_at' => 'datetime',
        ];
    }

    public function lottery()
    {
        return $this->belongsTo(Lottery::class);
    }

    public function winners()
    {
        return $this->hasMany(LotteryWinner::class);
    }
}

