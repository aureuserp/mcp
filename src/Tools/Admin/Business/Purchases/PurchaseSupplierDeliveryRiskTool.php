<?php

namespace Webkul\Mcp\Tools\Admin\Business\Purchases;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class PurchaseSupplierDeliveryRiskTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Identify supplier delivery and receipt risk indicators.
    MARKDOWN;

    protected function metric(): string
    {
        return 'purchase_supplier_delivery_risk';
    }
}
