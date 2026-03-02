<?php

namespace Webkul\Mcp\Tools\Admin\Business\Projects;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class ProjectStatusOverviewTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize active vs inactive project distribution.
    MARKDOWN;

    protected function metric(): string
    {
        return 'project_status_overview';
    }

    protected function pluginName(): string
    {
        return 'projects';
    }
}
