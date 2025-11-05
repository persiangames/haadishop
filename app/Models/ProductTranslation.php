<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    protected $table = 'product_translations';

    protected $fillable = [
        'product_id',
        'locale',
        'title',
        'short_desc',
        'long_desc',
        'meta_title',
        'meta_desc',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

