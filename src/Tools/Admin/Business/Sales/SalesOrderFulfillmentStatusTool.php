<?php

namespace Webkul\Mcp\Tools\Admin\Business\Sales;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class SalesOrderFulfillmentStatusTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return sales delivery status and commitment-date coverage.
    MARKDOWN;

    protected function metric(): string
    {
        return 'sales_order_fulfillment_status';
    }

    protected function pluginName(): string
    {
        return 'sales';
    }
}
