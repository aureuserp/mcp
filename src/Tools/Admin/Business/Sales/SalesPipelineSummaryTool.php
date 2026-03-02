<?php

namespace Webkul\Mcp\Tools\Admin\Business\Sales;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class SalesPipelineSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize sales pipeline state, invoicing status, and value.
    MARKDOWN;

    protected function metric(): string
    {
        return 'sales_pipeline_summary';
    }

    protected function pluginName(): string
    {
        return 'sales';
    }
}
