<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InvoiceStatusBreakdownTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return invoice state, payment state, and move type breakdown.
    MARKDOWN;

    protected function metric(): string
    {
        return 'invoice_status_breakdown';
    }
}
