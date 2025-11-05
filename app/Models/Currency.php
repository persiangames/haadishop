<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'code';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'precision',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'precision' => 'integer',
        ];
    }

    public function exchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'base_currency', 'code');
    }
}

