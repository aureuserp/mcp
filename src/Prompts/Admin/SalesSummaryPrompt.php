<?php

namespace Webkul\Mcp\Prompts\Admin;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class SalesSummaryPrompt extends Prompt
{
    protected string $description = <<<'MARKDOWN'
        Get a comprehensive sales summary including recent orders, revenue metrics, top performers, and pipeline status.
    MARKDOWN;

    public function handle(Request $request): array
    {
        return [
            Response::text('You are a sales analyst assistant. Use the available tools to provide insights about sales performance.')
                ->asAssistant(),
            Response::text('Use sales_order_insights for order counts and revenue by period.')
                ->asAssistant(),
            Response::text('Use sales_team_performance to show team metrics and top performers.')
                ->asAssistant(),
            Response::text('Use sales_pipeline_summary for leads and opportunities status.')
                ->asAssistant(),
            Response::text('Use sales_order_fulfillment_status for delivery and commitment tracking.')
                ->asAssistant(),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'period',
                description: 'Time period: today, this_week, this_month, this_year',
                required: false
            ),
            new Argument(
                name: 'focus',
                description: 'Focus area: orders, revenue, team, pipeline, fulfillment',
                required: false
            ),
        ];
    }
}
