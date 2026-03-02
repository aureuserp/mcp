<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountPaymentStateBreakdownTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return accounting move counts by payment state.
    MARKDOWN;

    protected function metric(): string
    {
        return 'account_payment_state_breakdown';
    }

    protected function pluginName(): string
    {
        return 'accounts';
    }
}
