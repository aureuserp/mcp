<?php

namespace Webkul\Mcp\Tools\Admin\Business\Purchases;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class PurchaseOrdersPendingTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return pending purchase order counts and state breakdown.
    MARKDOWN;

    protected function metric(): string
    {
        return 'purchase_orders_pending';
    }

    protected function pluginName(): string
    {
        return 'purchases';
    }
}
