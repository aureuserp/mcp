<?php

namespace Webkul\Mcp\Tools\Admin\Business\Purchases;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class PurchaseSpendSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return purchase spend totals across key amount fields.
    MARKDOWN;

    protected function metric(): string
    {
        return 'purchase_spend_summary';
    }

    protected function pluginName(): string
    {
        return 'purchases';
    }
}
