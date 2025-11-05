<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'payment_id',
        'gateway_txn_id',
        'raw_payload',
        'status',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}

