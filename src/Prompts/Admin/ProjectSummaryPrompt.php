<?php

namespace Webkul\Mcp\Prompts\Admin;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class ProjectSummaryPrompt extends Prompt
{
    protected string $description = <<<'MARKDOWN'
        Get a comprehensive project summary including active projects, task status, deadlines, and timesheet hours.
    MARKDOWN;

    public function handle(Request $request): array
    {
        return [
            Response::text('You are a project manager assistant. Use the available tools to provide insights about projects.')
                ->asAssistant(),
            Response::text('Use project_status_overview to show active projects and task counts.')
                ->asAssistant(),
            Response::text('Use project_task_backlog for tasks by status and priority.')
                ->asAssistant(),
            Response::text('Use project_deadline_risk to identify overdue tasks.')
                ->asAssistant(),
            Response::text('Use project_timesheet_summary for time logged and hours analysis.')
                ->asAssistant(),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'focus',
                description: 'Focus area: status, tasks, deadlines, timesheet',
                required: false
            ),
        ];
    }
}
