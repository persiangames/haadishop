<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecommendationController extends Controller
{
    protected RecommendationService $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * دریافت پیشنهادات شخصی‌شده
     */
    public function personalized(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->session()->getId() ?? Str::random(40);
        $limit = $request->get('limit', 10);

        $recommendations = $this->recommendationService->getPersonalizedRecommendations(
            $user?->id,
            $sessionId,
            $limit
        );

        return response()->json([
            'recommendations' => $recommendations->map(function ($product) {
                $translation = $product->translation(app()->getLocale());
                return [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'title' => $translation->title ?? null,
                    'short_desc' => $translation->short_desc ?? null,
                    'brand' => $product->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->translation(app()->getLocale())->name ?? null,
                    ] : null,
                    'categories' => $product->categories->map(function ($cat) {
                        $catTrans = $cat->translation(app()->getLocale());
                        return [
                            'id' => $cat->id,
                            'slug' => $cat->slug,
                            'name' => $catTrans->name ?? null,
                        ];
                    }),
                    'min_price' => $product->variants->map(function ($variant) {
                        $price = $variant->getPrice(app('currency') ?? 'IRR');
                        return $price ? $price->amount : null;
                    })->filter()->min(),
                ];
            }),
        ], 200);
    }

    /**
     * دریافت محصولات مرتبط
     */
    public function related(Request $request, $productId)
    {
        $limit = $request->get('limit', 8);

        $related = $this->recommendationService->getRelatedProducts($productId, $limit);

        return response()->json([
            'related_products' => $related->map(function ($product) {
                $translation = $product->translation(app()->getLocale());
                return [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'title' => $translation->title ?? null,
                    'short_desc' => $translation->short_desc ?? null,
                    'brand' => $product->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->translation(app()->getLocale())->name ?? null,
                    ] : null,
                    'min_price' => $product->variants->map(function ($variant) {
                        $price = $variant->getPrice(app('currency') ?? 'IRR');
                        return $price ? $price->amount : null;
                    })->filter()->min(),
                ];
            }),
        ], 200);
    }

    /**
     * دریافت محصولات محبوب
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);

        $popular = $this->recommendationService->getPopularProducts($limit);

        return response()->json([
            'popular_products' => $popular->map(function ($product) {
                $translation = $product->translation(app()->getLocale());
                return [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'title' => $translation->title ?? null,
                    'short_desc' => $translation->short_desc ?? null,
                    'views_count' => $product->views_count ?? 0,
                    'orders_count' => $product->order_items_count ?? 0,
                    'min_price' => $product->variants->map(function ($variant) {
                        $price = $variant->getPrice(app('currency') ?? 'IRR');
                        return $price ? $price->amount : null;
                    })->filter()->min(),
                ];
            }),
        ], 200);
    }

    /**
     * دریافت پیشنهادات بر اساس تاریخچه خرید
     */
    public function purchaseHistory(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Authentication required.',
            ], 401);
        }

        $limit = $request->get('limit', 10);

        $recommendations = $this->recommendationService->getPurchaseHistoryRecommendations(
            $user->id,
            $limit
        );

        return response()->json([
            'recommendations' => $recommendations->map(function ($product) {
                $translation = $product->translation(app()->getLocale());
                return [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'title' => $translation->title ?? null,
                    'short_desc' => $translation->short_desc ?? null,
                    'min_price' => $product->variants->map(function ($variant) {
                        $price = $variant->getPrice(app('currency') ?? 'IRR');
                        return $price ? $price->amount : null;
                    })->filter()->min(),
                ];
            }),
        ], 200);
    }
}

