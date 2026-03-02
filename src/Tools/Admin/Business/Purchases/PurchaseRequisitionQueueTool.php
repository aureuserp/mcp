<?php

namespace Webkul\Mcp\Tools\Admin\Business\Purchases;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class PurchaseRequisitionQueueTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize purchase requisition queue and open requisitions.
    MARKDOWN;

    protected function metric(): string
    {
        return 'purchase_requisition_queue';
    }
}
