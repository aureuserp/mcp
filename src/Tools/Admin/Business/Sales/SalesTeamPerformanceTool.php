<?php

namespace Webkul\Mcp\Tools\Admin\Business\Sales;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class SalesTeamPerformanceTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return sales team coverage and order volume indicators.
    MARKDOWN;

    protected function metric(): string
    {
        return 'sales_team_performance';
    }

    protected function pluginName(): string
    {
        return 'sales';
    }
}
