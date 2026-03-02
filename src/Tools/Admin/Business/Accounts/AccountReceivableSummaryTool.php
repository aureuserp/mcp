<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountReceivableSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize open accounts receivable count and amount.
    MARKDOWN;

    protected function metric(): string
    {
        return 'account_receivable_summary';
    }

    protected function pluginName(): string
    {
        return 'accounts';
    }
}
