<?php

namespace Webkul\Mcp\Tools\Admin\Business\Inventories;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InventoryWarehouseKpisTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return warehouse-level operational KPI counters.
    MARKDOWN;

    protected function metric(): string
    {
        return 'inventory_warehouse_kpis';
    }

    protected function pluginName(): string
    {
        return 'inventories';
    }
}
