<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class CreditNotesOverviewTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return a breakdown of customer credit notes (out_refund) and vendor refunds (in_refund):
        counts, amounts, open/unpaid residuals, and top partners by refund value.
    MARKDOWN;

    protected function metric(): string
    {
        return 'credit_notes_overview';
    }

    protected function pluginName(): string
    {
        return 'invoices';
    }
}
