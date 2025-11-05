<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateClick extends Model
{
    protected $table = 'affiliate_clicks';

    protected $fillable = [
        'affiliate_user_id',
        'product_id',
        'ref_code',
        'landing_url',
        'ip',
        'user_agent',
    ];

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

