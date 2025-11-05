<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ABTest extends Model
{
    protected $table = 'ab_tests';

    protected $fillable = [
        'key',
        'variants',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
        ];
    }

    public function assignments()
    {
        return $this->hasMany(ABTestAssignment::class);
    }

    public function metrics()
    {
        return $this->hasMany(ABTestMetric::class);
    }
}

