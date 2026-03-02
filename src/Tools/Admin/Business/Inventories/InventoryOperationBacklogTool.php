<?php

namespace Webkul\Mcp\Tools\Admin\Business\Inventories;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InventoryOperationBacklogTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize inventory operations by workflow state.
    MARKDOWN;

    protected function metric(): string
    {
        return 'inventory_operation_backlog';
    }

    protected function pluginName(): string
    {
        return 'inventories';
    }
}
