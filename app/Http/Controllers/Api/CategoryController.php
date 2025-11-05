<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());
        
        $categories = Category::with(['parent', 'children'])
            ->where('is_active', true)
            ->get()
            ->map(function ($category) use ($locale) {
                $translation = $category->translation($locale);
                return [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $translation->name ?? null,
                    'description' => $translation->description ?? null,
                    'parent_id' => $category->parent_id,
                    'parent' => $category->parent ? [
                        'id' => $category->parent->id,
                        'slug' => $category->parent->slug,
                        'name' => $category->parent->translation($locale)->name ?? null,
                    ] : null,
                    'children' => $category->children->map(function ($child) use ($locale) {
                        $childTrans = $child->translation($locale);
                        return [
                            'id' => $child->id,
                            'slug' => $child->slug,
                            'name' => $childTrans->name ?? null,
                        ];
                    }),
                ];
            });

        return response()->json([
            'categories' => $categories,
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $locale = $request->get('locale', app()->getLocale());
        
        $category = Category::with(['parent', 'children', 'products'])
            ->findOrFail($id);
        
        $translation = $category->translation($locale);

        return response()->json([
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $translation->name ?? null,
                'description' => $translation->description ?? null,
                'parent_id' => $category->parent_id,
                'is_active' => $category->is_active,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'slug' => $category->parent->slug,
                    'name' => $category->parent->translation($locale)->name ?? null,
                ] : null,
                'children_count' => $category->children->count(),
                'products_count' => $category->products->count(),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'unique:categories'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'translations' => ['required', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10'],
            'translations.*.name' => ['required', 'string', 'max:191'],
            'translations.*.description' => ['nullable', 'string'],
        ]);

        $category = Category::create([
            'slug' => $validated['slug'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['translations'] as $translation) {
            $category->translations()->create($translation);
        }

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category->load('translations'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'slug' => ['sometimes', 'string', 'max:191', 'unique:categories,slug,' . $id],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'translations' => ['sometimes', 'array'],
            'translations.*.locale' => ['required', 'string', 'max:10'],
            'translations.*.name' => ['required', 'string', 'max:191'],
            'translations.*.description' => ['nullable', 'string'],
        ]);

        $category->update([
            'slug' => $validated['slug'] ?? $category->slug,
            'parent_id' => $validated['parent_id'] ?? $category->parent_id,
            'is_active' => $validated['is_active'] ?? $category->is_active,
        ]);

        if (isset($validated['translations'])) {
            foreach ($validated['translations'] as $translation) {
                $category->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    $translation
                );
            }
        }

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => $category->fresh(['translations']),
        ], 200);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        // بررسی اینکه آیا دسته‌بندی محصول دارد یا نه
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with products.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ], 200);
    }
}

