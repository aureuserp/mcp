<?php

namespace Webkul\Mcp\Tools\Admin\Business\Projects;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class ProjectDeadlineRiskTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Summarize overdue and due-today project task risk.
    MARKDOWN;

    protected function metric(): string
    {
        return 'project_deadline_risk';
    }

    protected function pluginName(): string
    {
        return 'projects';
    }
}
