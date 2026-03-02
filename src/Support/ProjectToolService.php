<?php

namespace Webkul\Mcp\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\Timesheet;

class ProjectToolService
{
    use HasQueryHelpers;

    public function projectStatusOverview(): array
    {
        $model = Project::class;
        $today = Carbon::today()->toDateString();
        $activeScope = fn (Builder $query) => $query->where('is_active', true);

        return [
            'total_projects'          => $this->count($model),
            'active_projects'         => $this->count($model, $activeScope),
            'inactive_projects'       => $this->count($model, fn (Builder $query) => $query->where('is_active', false)),
            'overdue_active_projects' => $this->count($model, fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', $today)),
            'projects_by_manager' => $this->groupCountLimit($model, 'user_id', $activeScope),
        ];
    }

    public function projectTaskBacklog(): array
    {
        $model = Task::class;
        $openScope = fn (Builder $query) => $query->whereNotIn('state', ['done', 'cancelled']);
        $openTasks = $this->count($model, $openScope);

        $topAssignees = DB::table('projects_task_users')
            ->join('projects_tasks', 'projects_task_users.task_id', '=', 'projects_tasks.id')
            ->whereNotIn('projects_tasks.state', ['done', 'cancelled'])
            ->select('projects_task_users.user_id as id', DB::raw('COUNT(*) as count'))
            ->groupBy('projects_task_users.user_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['key' => (string) ($row->id ?? 'null'), 'total' => (int) $row->count])
            ->toArray();

        return [
            'states'                      => $this->groupCount($model, 'state'),
            'open_tasks'                  => $openTasks,
            'high_priority_open_tasks'    => $this->count($model, fn (Builder $query) => $query
                ->whereNotIn('state', ['done', 'cancelled'])
                ->where('priority', true)),
            'unassigned_open_tasks'       => $this->count($model, fn (Builder $query) => $query
                ->whereNotIn('state', ['done', 'cancelled'])
                ->doesntHave('users')),
            'top_projects_by_open_tasks'  => $this->groupCountLimit($model, 'project_id', $openScope),
            'top_assignees_by_open_tasks' => $topAssignees,
        ];
    }

    public function projectDeadlineRisk(): array
    {
        $model = Task::class;
        $today = Carbon::today();
        $todayDate = $today->toDateString();
        $todayDateTime = $today->toDateTimeString();
        $overdueScope = fn (Builder $query) => $query
            ->whereNotNull('deadline')
            ->where('deadline', '<', $todayDateTime)
            ->whereNotIn('state', ['done', 'cancelled']);

        $topAssigneesOverdue = DB::table('projects_task_users')
            ->join('projects_tasks', 'projects_task_users.task_id', '=', 'projects_tasks.id')
            ->whereNotNull('projects_tasks.deadline')
            ->where('projects_tasks.deadline', '<', $todayDateTime)
            ->whereNotIn('projects_tasks.state', ['done', 'cancelled'])
            ->select('projects_task_users.user_id as id', DB::raw('COUNT(*) as count'))
            ->groupBy('projects_task_users.user_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['key' => (string) ($row->id ?? 'null'), 'total' => (int) $row->count])
            ->toArray();

        return [
            'overdue_tasks'   => $this->count($model, $overdueScope),
            'due_today_tasks' => $this->count($model, fn (Builder $query) => $query
                ->whereDate('deadline', $todayDate)
                ->whereNotIn('state', ['done', 'cancelled'])),
            'due_next_7_days' => $this->count($model, fn (Builder $query) => $query
                ->whereBetween('deadline', [$todayDate, $today->copy()->addDays(7)->toDateString()])
                ->whereNotIn('state', ['done', 'cancelled'])),
            'unassigned_overdue_tasks' => $this->count($model, fn (Builder $query) => $query
                ->whereNotNull('deadline')
                ->where('deadline', '<', $todayDateTime)
                ->whereNotIn('state', ['done', 'cancelled'])
                ->doesntHave('users')),
            'top_projects_by_overdue_tasks'    => $this->groupCountLimit($model, 'project_id', $overdueScope),
            'top_assignees_with_overdue_tasks' => $topAssigneesOverdue,
        ];
    }

    public function projectTimesheetSummary(): array
    {
        $model = Timesheet::class;
        $today = Carbon::today();
        $entries = $this->count($model);
        $hours = $this->sum($model, 'unit_amount');

        return [
            'entries'               => $entries,
            'hours_logged'          => $hours,
            'avg_hours_per_entry'   => $this->ratio($hours, $entries),
            'hours_this_week'       => $this->sum($model, 'unit_amount', fn (Builder $query) => $query
                ->whereDate('date', '>=', $today->copy()->startOfWeek()->toDateString())),
            'hours_this_month'      => $this->sum($model, 'unit_amount', fn (Builder $query) => $query
                ->whereDate('date', '>=', $today->copy()->startOfMonth()->toDateString())),
            'top_projects_by_hours' => $this->groupSumLimit($model, 'project_id', 'unit_amount'),
            'top_users_by_hours'    => $this->groupSumLimit($model, 'user_id', 'unit_amount'),
        ];
    }
}
