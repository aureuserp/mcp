<?php

namespace Webkul\Mcp\Tools\Admin\Business\Inventories;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InventoryLocationBalanceTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return inventory location and on-hand quantity summary.
    MARKDOWN;

    protected function metric(): string
    {
        return 'inventory_location_balance';
    }

    protected function pluginName(): string
    {
        return 'inventories';
    }
}
