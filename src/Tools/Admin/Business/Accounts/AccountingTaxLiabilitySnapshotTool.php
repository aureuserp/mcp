<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountingTaxLiabilitySnapshotTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return draft and posted tax liability snapshot.
    MARKDOWN;

    protected function metric(): string
    {
        return 'accounting_tax_liability_snapshot';
    }
}
