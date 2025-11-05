<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPoint extends Model
{
    protected $table = 'loyalty_points';

    protected $fillable = [
        'user_id',
        'source',
        'points',
        'occurred_at',
        'expires_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'bigInteger',
            'occurred_at' => 'datetime',
            'expires_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

