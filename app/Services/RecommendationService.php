<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\ProductView;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Collaborative Filtering: Item-based
     * پیشنهاد محصولات بر اساس محصولات مشابه
     */
    public function getItemBasedRecommendations($productId, $limit = 10)
    {
        $cacheKey = "recommendations:item-based:product:{$productId}:limit:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($productId, $limit) {
            // دریافت کاربرانی که این محصول را خریداری کرده‌اند
            $usersWhoBought = OrderItem::whereHas('order', function ($q) {
                $q->where('status', 'paid');
            })
            ->whereHas('variant.product', function ($q) use ($productId) {
                $q->where('products.id', $productId);
            })
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->distinct()
            ->pluck('orders.user_id')
            ->filter();

            if ($usersWhoBought->isEmpty()) {
                return $this->getPopularProducts($limit);
            }

            // دریافت محصولات دیگر که این کاربران خریداری کرده‌اند
            $similarProducts = OrderItem::whereHas('order', function ($q) use ($usersWhoBought) {
                $q->where('status', 'paid')
                  ->whereIn('user_id', $usersWhoBought);
            })
            ->whereHas('variant.product', function ($q) use ($productId) {
                $q->where('products.id', '!=', $productId)
                  ->where('is_published', true);
            })
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('products.id', DB::raw('COUNT(*) as similarity_score'))
            ->groupBy('products.id')
            ->orderBy('similarity_score', 'desc')
            ->limit($limit)
            ->pluck('id');

            return Product::whereIn('id', $similarProducts)
                ->where('is_published', true)
                ->with(['brand', 'categories', 'variants.prices'])
                ->get();
        });
    }

    /**
     * Collaborative Filtering: User-based
     * پیشنهاد محصولات بر اساس کاربران مشابه
     */
    public function getUserBasedRecommendations($userId, $limit = 10)
    {
        $cacheKey = "recommendations:user-based:user:{$userId}:limit:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(12), function () use ($userId, $limit) {
            // دریافت محصولات خریداری شده توسط کاربر
            $userPurchasedProducts = OrderItem::whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('status', 'paid');
            })
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->distinct()
            ->pluck('product_variants.product_id');

            if ($userPurchasedProducts->isEmpty()) {
                return $this->getPopularProducts($limit);
            }

            // پیدا کردن کاربران مشابه (که محصولات مشابه خریداری کرده‌اند)
            $similarUsers = OrderItem::whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', '!=', $userId)
                  ->where('status', 'paid');
            })
            ->whereHas('variant.product', function ($q) use ($userPurchasedProducts) {
                $q->whereIn('products.id', $userPurchasedProducts);
            })
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('orders.user_id', DB::raw('COUNT(DISTINCT product_variants.product_id) as similarity_score'))
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->groupBy('orders.user_id')
            ->having('similarity_score', '>', 0)
            ->orderBy('similarity_score', 'desc')
            ->limit(10)
            ->pluck('user_id');

            if ($similarUsers->isEmpty()) {
                return $this->getPopularProducts($limit);
            }

            // دریافت محصولات خریداری شده توسط کاربران مشابه
            $recommendedProducts = OrderItem::whereHas('order', function ($q) use ($similarUsers, $userId) {
                $q->whereIn('user_id', $similarUsers)
                  ->where('status', 'paid');
            })
            ->whereHas('variant.product', function ($q) use ($userPurchasedProducts) {
                $q->whereNotIn('products.id', $userPurchasedProducts)
                  ->where('is_published', true);
            })
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('products.id', DB::raw('COUNT(*) as recommendation_score'))
            ->groupBy('products.id')
            ->orderBy('recommendation_score', 'desc')
            ->limit($limit)
            ->pluck('id');

            return Product::whereIn('id', $recommendedProducts)
                ->where('is_published', true)
                ->with(['brand', 'categories', 'variants.prices'])
                ->get();
        });
    }

    /**
     * پیشنهادات شخصی‌شده بر اساس تاریخچه بازدید و خرید
     */
    public function getPersonalizedRecommendations($userId = null, $sessionId = null, $limit = 10)
    {
        if ($userId) {
            return $this->getUserBasedRecommendations($userId, $limit);
        }

        if ($sessionId) {
            return $this->getSessionBasedRecommendations($sessionId, $limit);
        }

        return $this->getPopularProducts($limit);
    }

    /**
     * پیشنهادات بر اساس session (برای کاربران غیرلاگین)
     */
    public function getSessionBasedRecommendations($sessionId, $limit = 10)
    {
        $cacheKey = "recommendations:session:{$sessionId}:limit:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($sessionId, $limit) {
            // دریافت محصولات مشاهده شده
            $viewedProducts = ProductView::where('session_id', $sessionId)
                ->where('viewed_at', '>=', now()->subDays(7))
                ->distinct()
                ->pluck('product_id');

            if ($viewedProducts->isEmpty()) {
                return $this->getPopularProducts($limit);
            }

            // پیشنهاد محصولات مشابه
            return $this->getItemBasedRecommendations($viewedProducts->first(), $limit);
        });
    }

    /**
     * محصولات محبوب (Popular Products)
     */
    public function getPopularProducts($limit = 10)
    {
        $cacheKey = "recommendations:popular:limit:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($limit) {
            return Product::where('is_published', true)
                ->withCount(['views', 'orderItems'])
                ->orderBy('order_items_count', 'desc')
                ->orderBy('views_count', 'desc')
                ->limit($limit)
                ->with(['brand', 'categories', 'variants.prices'])
                ->get();
        });
    }

    /**
     * محصولات مرتبط (Related Products)
     */
    public function getRelatedProducts($productId, $limit = 8)
    {
        $product = Product::findOrFail($productId);
        
        // ترکیب چند روش:
        // 1. محصولات همان دسته‌بندی
        // 2. محصولات همان برند
        // 3. Item-based recommendations
        
        $categoryIds = $product->categories->pluck('id');
        
        $related = Product::where('id', '!=', $productId)
            ->where('is_published', true)
            ->where(function ($q) use ($categoryIds, $product) {
                $q->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                })
                ->orWhere('brand_id', $product->brand_id);
            })
            ->withCount(['views', 'orderItems'])
            ->orderBy('order_items_count', 'desc')
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->with(['brand', 'categories', 'variants.prices'])
            ->get();

        if ($related->count() < $limit) {
            // تکمیل با Item-based recommendations
            $itemBased = $this->getItemBasedRecommendations($productId, $limit - $related->count());
            $related = $related->merge($itemBased);
        }

        return $related->take($limit);
    }

    /**
     * پیشنهادات بر اساس تاریخچه خرید
     */
    public function getPurchaseHistoryRecommendations($userId, $limit = 10)
    {
        $cacheKey = "recommendations:purchase-history:user:{$userId}:limit:{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(12), function () use ($userId, $limit) {
            // دریافت دسته‌بندی‌های محصولات خریداری شده
            $purchasedCategories = OrderItem::whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('status', 'paid');
            })
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('product_categories', 'products.id', '=', 'product_categories.product_id')
            ->distinct()
            ->pluck('product_categories.category_id');

            if ($purchasedCategories->isEmpty()) {
                return $this->getPopularProducts($limit);
            }

            // پیشنهاد محصولات از همان دسته‌بندی‌ها
            return Product::whereHas('categories', function ($q) use ($purchasedCategories) {
                $q->whereIn('categories.id', $purchasedCategories);
            })
            ->where('is_published', true)
            ->withCount('orderItems')
            ->orderBy('order_items_count', 'desc')
            ->limit($limit)
            ->with(['brand', 'categories', 'variants.prices'])
            ->get();
        });
    }

    /**
     * پاک کردن cache پیشنهادات
     */
    public function clearRecommendationCache($userId = null, $productId = null)
    {
        if ($userId) {
            Cache::forget("recommendations:user-based:user:{$userId}:*");
            Cache::forget("recommendations:purchase-history:user:{$userId}:*");
        }

        if ($productId) {
            Cache::forget("recommendations:item-based:product:{$productId}:*");
        }

        Cache::forget("recommendations:popular:*");
    }
}

