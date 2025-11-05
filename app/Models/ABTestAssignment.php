<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ABTestAssignment extends Model
{
    protected $table = 'ab_test_assignments';

    protected $fillable = [
        'ab_test_id',
        'user_id',
        'session_id',
        'variant',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
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
}

