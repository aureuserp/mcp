<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountingJournalEntryHealthTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Report accounting journal entry totals by posting state.
    MARKDOWN;

    protected function metric(): string
    {
        return 'accounting_journal_entry_health';
    }
}
