<?php

namespace Webkul\Mcp\Tools\Admin\Business\Projects;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class ProjectTaskBacklogTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Return project task state distribution and open backlog count.
    MARKDOWN;

    protected function metric(): string
    {
        return 'project_task_backlog';
    }

    protected function pluginName(): string
    {
        return 'projects';
    }
}
