<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RatingService
{
    /**
     * محاسبه میانگین امتیاز محصول
     */
    public function calculateProductRating($productId)
    {
        // TODO: پیاده‌سازی جدول reviews و ratings
        // فعلاً بر اساس تعداد خرید و بازدید محاسبه می‌کنیم
        
        $product = Product::findOrFail($productId);
        
        $ordersCount = $product->orderItems()->count();
        $viewsCount = $product->views()->count();
        
        // محاسبه rating بر اساس تعداد خرید و بازدید
        $rating = 0;
        if ($viewsCount > 0) {
            $conversionRate = ($ordersCount / $viewsCount) * 100;
            $rating = min(5, max(0, ($conversionRate / 20) * 5)); // نرمال‌سازی به 0-5
        }
        
        return [
            'rating' => round($rating, 2),
            'orders_count' => $ordersCount,
            'views_count' => $viewsCount,
        ];
    }

    /**
     * دریافت محصولات با بالاترین امتیاز
     */
    public function getTopRatedProducts($limit = 10)
    {
        $products = Product::where('is_published', true)
            ->withCount(['views', 'orderItems'])
            ->get()
            ->map(function ($product) {
                $stats = $this->calculateProductRating($product->id);
                return [
                    'product' => $product,
                    'rating' => $stats['rating'],
                ];
            })
            ->sortByDesc('rating')
            ->take($limit)
            ->pluck('product');

        return $products;
    }
}

