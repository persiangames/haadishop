<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());
        
        $brands = Brand::where('is_active', true)
            ->get()
            ->map(function ($brand) use ($locale) {
                $translation = $brand->translation($locale);
                return [
                    'id' => $brand->id,
                    'slug' => $brand->slug,
                    'name' => $translation->name ?? null,
                    'description' => $translation->description ?? null,
                    'products_count' => $brand->products->count(),
                ];
            });

        return response()->json([
            'brands' => $brands,
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $locale = $request->get('locale', app()->getLocale());
        
        $brand = Brand::with('products')->findOrFail($id);
        $translation = $brand->translation($locale);

        return response()->json([
            'brand' => [
                'id' => $brand->id,
                'slug' => $brand->slug,
                'name' => $translation->name ?? null,
                'description' => $translation->description ?? null,
                'is_active' => $brand->is_active,
                'products_count' => $brand->products->count(),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'unique:brands'],
            'is_active' => ['sometimes', 'boolean'],
            'translations' => ['required', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10'],
            'translations.*.name' => ['required', 'string', 'max:191'],
            'translations.*.description' => ['nullable', 'string'],
        ]);

        $brand = Brand::create([
            'slug' => $validated['slug'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['translations'] as $translation) {
            $brand->translations()->create($translation);
        }

        return response()->json([
            'message' => 'Brand created successfully.',
            'brand' => $brand->load('translations'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'slug' => ['sometimes', 'string', 'max:191', 'unique:brands,slug,' . $id],
            'is_active' => ['sometimes', 'boolean'],
            'translations' => ['sometimes', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10'],
            'translations.*.name' => ['required', 'string', 'max:191'],
            'translations.*.description' => ['nullable', 'string'],
        ]);

        $brand->update([
            'slug' => $validated['slug'] ?? $brand->slug,
            'is_active' => $validated['is_active'] ?? $brand->is_active,
        ]);

        if (isset($validated['translations'])) {
            foreach ($validated['translations'] as $translation) {
                $brand->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    $translation
                );
            }
        }

        return response()->json([
            'message' => 'Brand updated successfully.',
            'brand' => $brand->fresh(['translations']),
        ], 200);
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        
        if ($brand->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete brand with products.',
            ], 422);
        }

        $brand->delete();

        return response()->json([
            'message' => 'Brand deleted successfully.',
        ], 200);
    }
}

