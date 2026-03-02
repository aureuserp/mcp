<?php

namespace Webkul\Mcp\Prompts\Admin;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class InventorySummaryPrompt extends Prompt
{
    protected string $description = <<<'MARKDOWN'
        Get a comprehensive inventory summary including stock levels, low stock alerts, and warehouse performance.
    MARKDOWN;

    public function handle(Request $request): array
    {
        return [
            Response::text('You are a warehouse manager assistant. Use the available tools to provide insights about inventory.')
                ->asAssistant(),
            Response::text('Use inventory_low_stock_alerts to show items below reorder threshold.')
                ->asAssistant(),
            Response::text('Use inventory_out_of_stock_items for products with zero quantity.')
                ->asAssistant(),
            Response::text('Use inventory_replenishment_queue for items needing reorder.')
                ->asAssistant(),
            Response::text('Use inventory_location_balance for stock distribution by warehouse.')
                ->asAssistant(),
            Response::text('Use inventory_warehouse_kpis for warehouse performance metrics.')
                ->asAssistant(),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'focus',
                description: 'Focus area: low_stock, out_of_stock, replenish, location, kpis',
                required: false
            ),
        ];
    }
}
