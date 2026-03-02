<?php

namespace Webkul\Mcp\Tools\Admin\Business\Projects;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class ProjectTimesheetSummaryTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return project timesheet entry and hours summary.
    MARKDOWN;

    protected function metric(): string
    {
        return 'project_timesheet_summary';
    }
}
