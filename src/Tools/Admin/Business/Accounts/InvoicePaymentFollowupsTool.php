<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class InvoicePaymentFollowupsTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Identify invoice follow-up candidates for collections.
    MARKDOWN;

    protected function metric(): string
    {
        return 'invoice_payment_followups';
    }
}
