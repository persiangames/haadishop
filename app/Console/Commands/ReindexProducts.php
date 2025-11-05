<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Console\Command;

class ReindexProducts extends Command
{
    protected $signature = 'search:reindex-products';
    protected $description = 'Reindex all products in Elasticsearch';

    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        parent::__construct();
        $this->searchService = $searchService;
    }

    public function handle()
    {
        if (!config('elasticsearch.enabled')) {
            $this->error('Elasticsearch is not enabled.');
            return 1;
        }

        $this->info('Starting product reindexing...');

        $products = Product::where('is_published', true)->get();
        $total = $products->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($products as $product) {
            $this->searchService->indexProduct($product);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Reindexed {$total} products successfully.");

        return 0;
    }
}

