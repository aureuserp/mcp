<?php

namespace Webkul\Mcp\Tools\Admin\Business\Inventories;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InventoryOutOfStockItemsTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize out-of-stock and negative stock rows.
    MARKDOWN;

    protected function metric(): string
    {
        return 'inventory_out_of_stock_items';
    }
}
