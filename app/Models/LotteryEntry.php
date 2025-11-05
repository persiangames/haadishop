<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryEntry extends Model
{
    protected $table = 'lottery_entries';

    protected $fillable = [
        'lottery_id',
        'order_id',
        'buyer_user_id',
        'affiliate_user_id',
        'lottery_code',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
        ];
    }

    public function lottery()
    {
        return $this->belongsTo(Lottery::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    public function winner()
    {
        return $this->hasOne(LotteryWinner::class);
    }

    public function generateLotteryCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
        } while (self::where('lottery_code', $code)->exists());

        return $code;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entry) {
            if (empty($entry->lottery_code)) {
                $entry->lottery_code = $entry->generateLotteryCode();
            }
        });
    }
}

