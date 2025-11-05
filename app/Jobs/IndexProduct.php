<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Product $product;
    protected string $action; // 'index', 'update', 'delete'

    public function __construct(Product $product, string $action = 'index')
    {
        $this->product = $product;
        $this->action = $action;
    }

    public function handle(SearchService $searchService)
    {
        if ($this->action === 'delete') {
            $searchService->deleteProduct($this->product->id);
        } else {
            $searchService->indexProduct($this->product);
        }
    }
}

