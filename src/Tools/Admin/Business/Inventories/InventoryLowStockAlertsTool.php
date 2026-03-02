<?php

namespace Webkul\Mcp\Tools\Admin\Business\Inventories;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InventoryLowStockAlertsTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return low-stock control points and replenishment candidates.
    MARKDOWN;

    protected function metric(): string
    {
        return 'inventory_low_stock_alerts';
    }
}
