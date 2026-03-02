<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountPayableSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize open accounts payable count and amount.
    MARKDOWN;

    protected function metric(): string
    {
        return 'account_payable_summary';
    }
}
