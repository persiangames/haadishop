<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ABTestMetric extends Model
{
    protected $table = 'ab_test_metrics';

    protected $fillable = [
        'ab_test_id',
        'user_id',
        'session_id',
        'event_key',
        'value',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function test()
    {
        return $this->belongsTo(ABTest::class, 'ab_test_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignment()
    {
        return $this->hasOne(ABTestAssignment::class, 'ab_test_id', 'ab_test_id')
            ->where(function ($q) {
                if ($this->user_id) {
                    $q->where('user_id', $this->user_id);
                } else {
                    $q->where('session_id', $this->session_id);
                }
            });
    }
}

