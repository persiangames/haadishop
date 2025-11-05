<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'brand_id',
        'slug',
        'sku',
        'status',
        'is_published',
        'weight',
        'width',
        'height',
        'length',
        'tax_class_id',
        'warranty_months',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'weight' => 'decimal:3',
            'width' => 'decimal:3',
            'height' => 'decimal:3',
            'length' => 'decimal:3',
        ];
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations()->where('locale', $locale)->first();
    }

    public function views()
    {
        return $this->hasMany(ProductView::class);
    }

    public function orderItems()
    {
        return $this->hasManyThrough(OrderItem::class, ProductVariant::class);
    }

    public function orderItemsCount()
    {
        return $this->hasManyThrough(OrderItem::class, ProductVariant::class)
            ->selectRaw('products.id, COUNT(*) as count')
            ->groupBy('products.id');
    }
}

