<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * جستجوی محصولات
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'brand_id' => ['sometimes', 'integer', 'exists:brands,id'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $filters = [];
        if ($request->has('category_id')) {
            $filters['category_id'] = $request->category_id;
        }
        if ($request->has('brand_id')) {
            $filters['brand_id'] = $request->brand_id;
        }
        if ($request->has('min_price')) {
            $filters['min_price'] = $request->min_price;
        }
        if ($request->has('max_price')) {
            $filters['max_price'] = $request->max_price;
        }

        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);

        $result = $this->searchService->searchProducts(
            $request->get('q', ''),
            $filters,
            $page,
            $perPage
        );

        return response()->json([
            'query' => $request->get('q', ''),
            'filters' => $filters,
            'products' => $result['products'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ], 200);
    }
}

