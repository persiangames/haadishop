<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\AffiliateService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected AffiliateService $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    public function show(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // ردیابی کلیک affiliate
        if ($request->has('ref')) {
            $this->affiliateService->trackClick(
                $request->ref,
                $product->id,
                $request
            );
        }

        // اگر lottery_code وجود دارد، آن را در session ذخیره می‌کنیم
        if ($request->has('lottery')) {
            session(['lottery_code' => $request->lottery]);
        }

        // TODO: نمایش صفحه محصول
        return view('product.show', compact('product'));
    }
}

