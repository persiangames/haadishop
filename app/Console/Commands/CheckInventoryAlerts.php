<?php

namespace App\Console\Commands;

use App\Services\InventoryAlertService;
use Illuminate\Console\Command;

class CheckInventoryAlerts extends Command
{
    protected $signature = 'inventory:check-alerts';
    protected $description = 'Check inventory and send low stock alerts';

    protected InventoryAlertService $inventoryAlertService;

    public function __construct(InventoryAlertService $inventoryAlertService)
    {
        parent::__construct();
        $this->inventoryAlertService = $inventoryAlertService;
    }

    public function handle()
    {
        $this->info('Checking inventory alerts...');

        $lowStockCount = $this->inventoryAlertService->checkLowStock();
        $this->info("Found {$lowStockCount} low stock items.");

        $outOfStockCount = $this->inventoryAlertService->checkOutOfStock();
        $this->info("Found {$outOfStockCount} out of stock items.");

        $this->info('Inventory alerts checked successfully.');
    }
}

