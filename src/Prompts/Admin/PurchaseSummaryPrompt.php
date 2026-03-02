<?php

namespace Webkul\Mcp\Prompts\Admin;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PurchaseSummaryPrompt extends Prompt
{
    protected string $description = <<<'MARKDOWN'
        Get a comprehensive purchase summary including pending orders, supplier performance, and spend analysis.
    MARKDOWN;

    public function handle(Request $request): array
    {
        return [
            Response::text('You are a procurement analyst assistant. Use the available tools to provide insights about purchasing.')
                ->asAssistant(),
            Response::text('Use purchase_orders_pending to show pending purchase orders and amounts.')
                ->asAssistant(),
            Response::text('Use purchase_spend_summary for spending analysis and top suppliers.')
                ->asAssistant(),
            Response::text('Use purchase_supplier_delivery_risk to identify supplier delivery risks.')
                ->asAssistant(),
            Response::text('Use purchase_requisition_queue for pending requisitions.')
                ->asAssistant(),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'period',
                description: 'Time period: this_week, this_month, this_year',
                required: false
            ),
            new Argument(
                name: 'focus',
                description: 'Focus area: pending, spend, suppliers, risks',
                required: false
            ),
        ];
    }
}
