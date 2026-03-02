<?php

namespace Webkul\Mcp\Prompts\Admin;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class InvoiceSummaryPrompt extends Prompt
{
    protected string $description = <<<'MARKDOWN'
        Get a comprehensive invoice summary including overdue invoices, aging analysis, and payment status.
    MARKDOWN;

    public function handle(Request $request): array
    {
        return [
            Response::text('You are a finance assistant. Use the available tools to provide insights about invoices and receivables.')
                ->asAssistant(),
            Response::text('Use invoice_overdue_summary to show overdue invoices and amounts.')
                ->asAssistant(),
            Response::text('Use invoice_aging_buckets for invoice aging analysis (0-7, 8-30, 31+ days).')
                ->asAssistant(),
            Response::text('Use invoice_status_breakdown for invoice states and payment status.')
                ->asAssistant(),
            Response::text('Use invoice_payment_followups to identify follow-up candidates.')
                ->asAssistant(),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'focus',
                description: 'Focus area: overdue, aging, status, followups',
                required: false
            ),
        ];
    }
}
