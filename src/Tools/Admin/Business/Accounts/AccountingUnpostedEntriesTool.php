<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountingUnpostedEntriesTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize draft accounting entries pending posting.
    MARKDOWN;

    protected function metric(): string
    {
        return 'accounting_unposted_entries';
    }
}
