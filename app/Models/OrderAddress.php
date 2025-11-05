<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    protected $table = 'order_addresses';

    protected $fillable = [
        'order_id',
        'type',
        'name',
        'phone',
        'country',
        'province',
        'city',
        'address_line',
        'postal_code',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

