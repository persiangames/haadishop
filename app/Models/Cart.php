<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'currency_code',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'currency_code' => 'string',
            'locale' => 'string',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function getTotalAttribute()
    {
        return $this->items->sum('line_total');
    }
}

