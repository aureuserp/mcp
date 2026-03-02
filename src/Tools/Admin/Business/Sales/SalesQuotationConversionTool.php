<?php

namespace Webkul\Mcp\Tools\Admin\Business\Sales;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class SalesQuotationConversionTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize quote-to-order conversion indicators.
    MARKDOWN;

    protected function metric(): string
    {
        return 'sales_quotation_conversion';
    }

    protected function pluginName(): string
    {
        return 'sales';
    }
}
