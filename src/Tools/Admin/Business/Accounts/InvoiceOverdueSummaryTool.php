<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InvoiceOverdueSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize overdue invoices and outstanding residual amount.
    MARKDOWN;

    protected function metric(): string
    {
        return 'invoice_overdue_summary';
    }

    protected function pluginName(): string
    {
        return 'invoices';
    }
}
