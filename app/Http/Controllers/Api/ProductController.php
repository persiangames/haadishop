<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductView;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());
        $currency = $request->get('currency', 'IRR');
        
        $query = Product::with(['brand', 'categories', 'variants.prices', 'variants.inventory'])
            ->where('is_published', true)
            ->where('status', 'published');

        // فیلتر بر اساس دسته‌بندی
        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // فیلتر بر اساس برند
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // جستجو
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('short_desc', 'like', "%{$search}%");
            });
        }

        // مرتب‌سازی
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        $products->getCollection()->transform(function ($product) use ($locale, $currency) {
            $translation = $product->translation($locale);
            $variants = $product->variants->map(function ($variant) use ($currency) {
                $price = $variant->getPrice($currency);
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'option_values' => $variant->option_values,
                    'price' => $price ? [
                        'amount' => $price->amount,
                        'compare_at_amount' => $price->compare_at_amount,
                        'currency' => $price->currency_code,
                    ] : null,
                    'inventory' => $variant->inventory ? [
                        'available' => $variant->inventory->available_quantity,
                        'in_stock' => $variant->inventory->available_quantity > 0,
                    ] : null,
                ];
            });

            return [
                'id' => $product->id,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'title' => $translation->title ?? null,
                'short_desc' => $translation->short_desc ?? null,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'slug' => $product->brand->slug,
                    'name' => $product->brand->translation($locale)->name ?? null,
                ] : null,
                'categories' => $product->categories->map(function ($cat) use ($locale) {
                    $catTrans = $cat->translation($locale);
                    return [
                        'id' => $cat->id,
                        'slug' => $cat->slug,
                        'name' => $catTrans->name ?? null,
                    ];
                }),
                'variants' => $variants,
                'min_price' => $variants->min(function ($v) {
                    return $v['price']['amount'] ?? null;
                }),
                'max_price' => $variants->max(function ($v) {
                    return $v['price']['amount'] ?? null;
                }),
            ];
        });

        return response()->json($products, 200);
    }

    public function show(Request $request, $slug)
    {
        $locale = $request->get('locale', app()->getLocale());
        $currency = $request->get('currency', 'IRR');
        
        $product = Product::with([
            'brand',
            'categories',
            'variants.prices',
            'variants.inventory',
            'translations'
        ])->where('slug', $slug)
          ->where('is_published', true)
          ->firstOrFail();

        $translation = $product->translation($locale);

        // ثبت بازدید
        ProductView::create([
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'product_id' => $product->id,
            'viewed_at' => now(),
        ]);

        $variants = $product->variants->map(function ($variant) use ($currency) {
            $price = $variant->getPrice($currency);
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'option_values' => $variant->option_values,
                'is_active' => $variant->is_active,
                'price' => $price ? [
                    'amount' => $price->amount,
                    'compare_at_amount' => $price->compare_at_amount,
                    'currency' => $price->currency_code,
                ] : null,
                'inventory' => $variant->inventory ? [
                    'quantity_on_hand' => $variant->inventory->quantity_on_hand,
                    'quantity_reserved' => $variant->inventory->quantity_reserved,
                    'available' => $variant->inventory->available_quantity,
                    'in_stock' => $variant->inventory->available_quantity > 0,
                    'low_stock' => $variant->inventory->isLowStock(),
                ] : null,
            ];
        });

        return response()->json([
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'title' => $translation->title ?? null,
                'short_desc' => $translation->short_desc ?? null,
                'long_desc' => $translation->long_desc ?? null,
                'meta_title' => $translation->meta_title ?? null,
                'meta_desc' => $translation->meta_desc ?? null,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'slug' => $product->brand->slug,
                    'name' => $product->brand->translation($locale)->name ?? null,
                ] : null,
                'categories' => $product->categories->map(function ($cat) use ($locale) {
                    $catTrans = $cat->translation($locale);
                    return [
                        'id' => $cat->id,
                        'slug' => $cat->slug,
                        'name' => $catTrans->name ?? null,
                    ];
                }),
                'variants' => $variants,
                'weight' => $product->weight,
                'width' => $product->width,
                'height' => $product->height,
                'length' => $product->length,
                'warranty_months' => $product->warranty_months,
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'unique:products'],
            'sku' => ['nullable', 'string', 'max:191', 'unique:products'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'status' => ['sometimes', 'in:draft,published'],
            'is_published' => ['sometimes', 'boolean'],
            'weight' => ['nullable', 'numeric'],
            'width' => ['nullable', 'numeric'],
            'height' => ['nullable', 'numeric'],
            'length' => ['nullable', 'numeric'],
            'warranty_months' => ['nullable', 'integer'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'translations' => ['required', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.short_desc' => ['nullable', 'string'],
            'translations.*.long_desc' => ['nullable', 'string'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_desc' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::create([
            'slug' => $validated['slug'],
            'sku' => $validated['sku'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'is_published' => $validated['is_published'] ?? false,
            'weight' => $validated['weight'] ?? null,
            'width' => $validated['width'] ?? null,
            'height' => $validated['height'] ?? null,
            'length' => $validated['length'] ?? null,
            'warranty_months' => $validated['warranty_months'] ?? null,
        ]);

        // افزودن ترجمه‌ها
        foreach ($validated['translations'] as $translation) {
            $product->translations()->create($translation);
        }

            // افزودن دسته‌بندی‌ها
            if (isset($validated['category_ids'])) {
                $product->categories()->sync($validated['category_ids']);
            }

            // Index در Elasticsearch
            if (config('elasticsearch.enabled')) {
                dispatch(new \App\Jobs\IndexProduct($product, 'index'));
            }

            return response()->json([
                'message' => 'Product created successfully.',
                'product' => $product->load(['translations', 'categories', 'brand']),
            ], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'slug' => ['sometimes', 'string', 'max:191', 'unique:products,slug,' . $id],
            'sku' => ['nullable', 'string', 'max:191', 'unique:products,sku,' . $id],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'status' => ['sometimes', 'in:draft,published'],
            'is_published' => ['sometimes', 'boolean'],
            'weight' => ['nullable', 'numeric'],
            'width' => ['nullable', 'numeric'],
            'height' => ['nullable', 'numeric'],
            'length' => ['nullable', 'numeric'],
            'warranty_months' => ['nullable', 'integer'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'translations' => ['sometimes', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.short_desc' => ['nullable', 'string'],
            'translations.*.long_desc' => ['nullable', 'string'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_desc' => ['nullable', 'string', 'max:255'],
        ]);

        $product->update([
            'slug' => $validated['slug'] ?? $product->slug,
            'sku' => $validated['sku'] ?? $product->sku,
            'brand_id' => $validated['brand_id'] ?? $product->brand_id,
            'status' => $validated['status'] ?? $product->status,
            'is_published' => $validated['is_published'] ?? $product->is_published,
            'weight' => $validated['weight'] ?? $product->weight,
            'width' => $validated['width'] ?? $product->width,
            'height' => $validated['height'] ?? $product->height,
            'length' => $validated['length'] ?? $product->length,
            'warranty_months' => $validated['warranty_months'] ?? $product->warranty_months,
        ]);

        // به‌روزرسانی ترجمه‌ها
        if (isset($validated['translations'])) {
            foreach ($validated['translations'] as $translation) {
                $product->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    $translation
                );
            }
        }

        // به‌روزرسانی دسته‌بندی‌ها
        if (isset($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        // Update در Elasticsearch
        if (config('elasticsearch.enabled')) {
            dispatch(new \App\Jobs\IndexProduct($product, 'update'));
        }

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh(['translations', 'categories', 'brand']),
        ], 200);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // بررسی اینکه آیا محصول سفارش دارد یا نه
        if ($product->variants()->whereHas('orderItems')->exists()) {
            return response()->json([
                'message' => 'Cannot delete product with existing orders.',
            ], 422);
        }

        // Delete از Elasticsearch
        if (config('elasticsearch.enabled')) {
            dispatch(new \App\Jobs\IndexProduct($product, 'delete'));
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ], 200);
    }
}

