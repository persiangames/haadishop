<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchService
{
    protected string $elasticsearchHost;
    protected string $indexPrefix;

    public function __construct()
    {
        $this->elasticsearchHost = config('elasticsearch.host', 'http://localhost:9200');
        $this->indexPrefix = config('elasticsearch.index_prefix', 'haadishop');
    }

    /**
     * Index کردن محصول
     */
    public function indexProduct(Product $product)
    {
        try {
            $indexName = $this->getIndexName('products');
            $this->ensureIndexExists($indexName);

            $translation = $product->translation('fa') ?? $product->translation('en');
            
            $document = [
                'id' => $product->id,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'title' => $translation->title ?? null,
                'short_desc' => $translation->short_desc ?? null,
                'long_desc' => $translation->long_desc ?? null,
                'brand_id' => $product->brand_id,
                'category_ids' => $product->categories->pluck('id')->toArray(),
                'status' => $product->status,
                'is_published' => $product->is_published,
                'created_at' => $product->created_at->toIso8601String(),
                'updated_at' => $product->updated_at->toIso8601String(),
            ];

            $response = Http::put("{$this->elasticsearchHost}/{$indexName}/_doc/{$product->id}", $document);

            if ($response->successful()) {
                return true;
            }

            Log::error('Failed to index product', [
                'product_id' => $product->id,
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error indexing product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * جستجوی محصولات
     */
    public function searchProducts($query, $filters = [], $page = 1, $perPage = 15)
    {
        try {
            $indexName = $this->getIndexName('products');

            $searchQuery = [
                'query' => [
                    'bool' => [
                        'must' => [],
                        'filter' => [],
                    ],
                ],
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
            ];

            // جستجوی متن
            if (!empty($query)) {
                $searchQuery['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['title^3', 'short_desc^2', 'long_desc'],
                        'type' => 'best_fields',
                        'fuzziness' => 'AUTO',
                    ],
                ];
            } else {
                $searchQuery['query']['bool']['must'][] = ['match_all' => new \stdClass()];
            }

            // فیلترها
            if (isset($filters['category_id'])) {
                $searchQuery['query']['bool']['filter'][] = [
                    'term' => ['category_ids' => $filters['category_id']],
                ];
            }

            if (isset($filters['brand_id'])) {
                $searchQuery['query']['bool']['filter'][] = [
                    'term' => ['brand_id' => $filters['brand_id']],
                ];
            }

            if (isset($filters['min_price']) || isset($filters['max_price'])) {
                // TODO: پیاده‌سازی فیلتر قیمت
            }

            // فقط محصولات منتشر شده
            $searchQuery['query']['bool']['filter'][] = [
                'term' => ['is_published' => true],
            ];

            $response = Http::post("{$this->elasticsearchHost}/{$indexName}/_search", $searchQuery);

            if ($response->successful()) {
                $data = $response->json();
                $hits = $data['hits']['hits'] ?? [];
                $total = $data['hits']['total']['value'] ?? 0;

                $productIds = collect($hits)->pluck('_id')->map(fn($id) => (int)$id)->toArray();

                return [
                    'products' => Product::whereIn('id', $productIds)
                        ->orderByRaw('FIELD(id, ' . implode(',', $productIds) . ')')
                        ->get(),
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                ];
            }

            Log::error('Elasticsearch search failed', [
                'query' => $query,
                'response' => $response->body(),
            ]);

            // Fallback to database search
            return $this->fallbackSearch($query, $filters, $page, $perPage);
        } catch (\Exception $e) {
            Log::error('Elasticsearch search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            // Fallback to database search
            return $this->fallbackSearch($query, $filters, $page, $perPage);
        }
    }

    /**
     * Fallback search در دیتابیس
     */
    protected function fallbackSearch($query, $filters, $page, $perPage)
    {
        $products = Product::where('is_published', true);

        if (!empty($query)) {
            $products->whereHas('translations', function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('short_desc', 'like', "%{$query}%");
            });
        }

        if (isset($filters['category_id'])) {
            $products->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        if (isset($filters['brand_id'])) {
            $products->where('brand_id', $filters['brand_id']);
        }

        $total = $products->count();
        $products = $products->paginate($perPage, ['*'], 'page', $page);

        return [
            'products' => $products->items(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * حذف محصول از index
     */
    public function deleteProduct($productId)
    {
        try {
            $indexName = $this->getIndexName('products');
            $response = Http::delete("{$this->elasticsearchHost}/{$indexName}/_doc/{$productId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to delete product from index', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ایجاد index در Elasticsearch
     */
    protected function ensureIndexExists($indexName)
    {
        $exists = Http::head("{$this->elasticsearchHost}/{$indexName}")->successful();

        if (!$exists) {
            $mapping = [
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'slug' => ['type' => 'keyword'],
                        'sku' => ['type' => 'keyword'],
                        'title' => [
                            'type' => 'text',
                            'analyzer' => 'standard',
                            'fields' => [
                                'keyword' => ['type' => 'keyword'],
                            ],
                        ],
                        'short_desc' => ['type' => 'text'],
                        'long_desc' => ['type' => 'text'],
                        'brand_id' => ['type' => 'integer'],
                        'category_ids' => ['type' => 'integer'],
                        'status' => ['type' => 'keyword'],
                        'is_published' => ['type' => 'boolean'],
                        'created_at' => ['type' => 'date'],
                        'updated_at' => ['type' => 'date'],
                    ],
                ],
            ];

            Http::put("{$this->elasticsearchHost}/{$indexName}", $mapping);
        }
    }

    protected function getIndexName($type)
    {
        return "{$this->indexPrefix}_{$type}";
    }
}

