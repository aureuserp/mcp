<?php

namespace Webkul\Mcp\Tools\Admin\Business\Sales;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class SalesOrderInsightsTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Get comprehensive sales order insights including:
        - Recent 5 sales orders with full details (customer, email, phone, products with SKU, quantities, prices)
        - Order totals (subtotal, tax, total)
        - Invoice status (payment status)
        - Delivery/fulfillment status
        - Commitment dates
        - Total value of the last 5 orders
        - Who created the most recent orders
        - Products/items in the last orders with names, SKUs, and quantities
        - Order counts and values for today, this week, this month, this year, last 7 days, last 30 days
        - Summary statistics (total orders, average order value, orders by state/status)
        - Top products, salespersons, and customers
        
        Use this tool to answer questions like:
        - "Show me the most recent 5 sales orders."
        - "What is the total value of the last orders?"
        - "Who created the most recent orders?"
        - "How many products/items do the last orders contain? List the product names."
        - "How many orders did we get today / this week / this month / this year?"
        - "What is the invoice status of recent orders?"
        - "What is the delivery status of orders?"
    MARKDOWN;

    protected function metric(): string
    {
        return 'sales_order_insights';
    }

    protected function pluginName(): string
    {
        return 'sales';
    }
}
