<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountingCashflowSnapshotTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize open receivable and payable exposure.
    MARKDOWN;

    protected function metric(): string
    {
        return 'accounting_cashflow_snapshot';
    }
}
