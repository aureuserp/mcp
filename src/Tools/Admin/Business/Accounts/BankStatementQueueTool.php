<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class BankStatementQueueTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize bank statement queue and workflow status.
    MARKDOWN;

    protected function metric(): string
    {
        return 'bank_statement_queue';
    }

    protected function pluginName(): string
    {
        return 'accounts';
    }
}
