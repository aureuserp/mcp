<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InvoiceAgingBucketsTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return aging buckets for unpaid and partially paid invoices.
    MARKDOWN;

    protected function metric(): string
    {
        return 'invoice_aging_buckets';
    }

    protected function pluginName(): string
    {
        return 'invoices';
    }
}
