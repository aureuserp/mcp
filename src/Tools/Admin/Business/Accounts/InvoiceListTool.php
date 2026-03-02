<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InvoiceListTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return invoice volume and state distribution for admins.
    MARKDOWN;

    protected function metric(): string
    {
        return 'invoice_list';
    }
}
