<?php

namespace Webkul\Mcp\Tools\Admin\Business\Inventories;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InventoryReplenishmentQueueTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Report replenishment queue and draft inventory operations.
    MARKDOWN;

    protected function metric(): string
    {
        return 'inventory_replenishment_queue';
    }

    protected function pluginName(): string
    {
        return 'inventories';
    }
}
