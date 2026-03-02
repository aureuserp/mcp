<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountMoveStateBreakdownTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return accounting move counts by workflow state.
    MARKDOWN;

    protected function metric(): string
    {
        return 'account_move_state_breakdown';
    }
}
