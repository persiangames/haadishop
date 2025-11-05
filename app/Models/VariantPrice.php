<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantPrice extends Model
{
    protected $fillable = [
        'product_variant_id',
        'currency_code',
        'amount',
        'compare_at_amount',
        'start_at',
        'end_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'compare_at_amount' => 'decimal:2',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
}

