<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lottery extends Model
{
    protected $table = 'lotteries';

    protected $fillable = [
        'product_id',
        'target_pool_amount',
        'current_pool_amount',
        'currency_code',
        'is_active',
        'auto_draw_threshold_percent',
    ];

    protected function casts(): array
    {
        return [
            'target_pool_amount' => 'decimal:2',
            'current_pool_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'auto_draw_threshold_percent' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function entries()
    {
        return $this->hasMany(LotteryEntry::class);
    }

    public function draws()
    {
        return $this->hasMany(LotteryDraw::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function getCompletionPercentAttribute(): float
    {
        if ($this->target_pool_amount == 0) {
            return 0;
        }
        return min(100, ($this->current_pool_amount / $this->target_pool_amount) * 100);
    }

    public function shouldAutoDraw(): bool
    {
        return $this->is_active && 
               $this->completion_percent >= $this->auto_draw_threshold_percent;
    }
}

