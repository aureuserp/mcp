<?php

namespace Webkul\Mcp\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

trait HasQueryHelpers
{
    protected function count(string $modelClass, ?callable $scope = null): int
    {
        if (! class_exists($modelClass)) {
            return 0;
        }

        try {
            $query = $this->baseQuery($modelClass);

            if ($scope) {
                $scope($query);
            }

            return (int) $query->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function sum(string $modelClass, string $column, ?callable $scope = null): float
    {
        if (! class_exists($modelClass)) {
            return 0.0;
        }

        try {
            $query = $this->baseQuery($modelClass);

            if ($scope) {
                $scope($query);
            }

            return (float) $query->sum($column);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    protected function average(string $modelClass, string $column, ?callable $scope = null): float
    {
        if (! class_exists($modelClass)) {
            return 0.0;
        }

        try {
            $query = $this->baseQuery($modelClass);

            if ($scope) {
                $scope($query);
            }

            return (float) $query->avg($column);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    protected function ratio(float|int $numerator, float|int $denominator): float
    {
        if ($denominator == 0) {
            return 0.0;
        }

        return round(((float) $numerator / (float) $denominator), 4);
    }

    /**
     * @return array<string, int>
     */
    protected function groupCount(string $modelClass, string $column, ?callable $scope = null): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $query = $this->baseQuery($modelClass);

            if ($scope) {
                $scope($query);
            }

            /** @var Collection<int, object{value:mixed, total:int}> $rows */
            $rows = $query
                ->selectRaw("{$column} as value, COUNT(*) as total")
                ->groupBy($column)
                ->get();

            return $rows
                ->mapWithKeys(function ($row): array {
                    return [(string) ($row->value ?? 'null') => (int) $row->total];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array{key:string,total:int}>
     */
    protected function groupCountLimit(string $modelClass, string $column, ?callable $scope = null, int $limit = 5): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $query = $this->baseQuery($modelClass);

            if ($scope) {
                $scope($query);
            }

            /** @var Collection<int, object{value:mixed,total:int}> $rows */
            $rows = $query
                ->selectRaw("{$column} as value, COUNT(*) as total")
                ->groupBy($column)
                ->orderByDesc('total')
                ->limit($limit)
                ->get();

            return $rows
                ->map(fn ($row): array => [
                    'key'   => (string) ($row->value ?? 'null'),
                    'total' => (int) $row->total,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array{key:string,total:float}>
     */
    protected function groupSumLimit(
        string $modelClass,
        string $groupColumn,
        string $sumColumn,
        ?callable $scope = null,
        int $limit = 5,
        string $order = 'desc'
    ): array {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $query = $this->baseQuery($modelClass);

            if ($scope) {
                $scope($query);
            }

            /** @var Collection<int, object{value:mixed,total:float}> $rows */
            $rows = $query
                ->selectRaw("{$groupColumn} as value, COALESCE(SUM({$sumColumn}), 0) as total")
                ->groupBy($groupColumn)
                ->orderBy('total', strtolower($order) === 'asc' ? 'asc' : 'desc')
                ->limit($limit)
                ->get();

            return $rows
                ->map(fn ($row): array => [
                    'key'   => (string) ($row->value ?? 'null'),
                    'total' => (float) $row->total,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function baseQuery(string $modelClass): Builder
    {
        /** @var Builder $query */
        $query = $modelClass::query();

        if (method_exists($modelClass, 'withoutGlobalScopes')) {
            /** @var Builder $query */
            $query = $modelClass::withoutGlobalScopes();
        }

        return $query;
    }

    /**
     * @param  array<int, array{key:string,total:int|float}>  $rows
     * @return array<int, array{id:int|null,label:string,total:int|float}>
     */
    protected function resolveRankedListLabels(array $rows, string $modelClass): array
    {
        if ($rows === []) {
            return [];
        }

        if (! class_exists($modelClass)) {
            return array_map(fn (array $row): array => [
                'id'    => is_numeric($row['key']) ? (int) $row['key'] : null,
                'label' => (string) $row['key'],
                'total' => $row['total'],
            ], $rows);
        }

        $ids = collect($rows)
            ->pluck('key')
            ->filter(fn (mixed $value): bool => is_numeric($value) && (int) $value > 0)
            ->map(fn (mixed $value): int => (int) $value)
            ->values()
            ->all();

        $labels = [];

        if ($ids !== []) {
            $nameColumn = $this->resolveDisplayColumn($modelClass);

            /** @var Collection<int, mixed> $labelRows */
            $labelRows = $this->baseQuery($modelClass)
                ->whereIn('id', $ids)
                ->pluck($nameColumn, 'id');

            $labels = $labelRows->all();
        }

        return array_map(function (array $row) use ($labels): array {
            $id = is_numeric($row['key']) ? (int) $row['key'] : null;

            return [
                'id'    => $id,
                'label' => ($id !== null && isset($labels[$id])) ? (string) $labels[$id] : (string) $row['key'],
                'total' => $row['total'],
            ];
        }, $rows);
    }

    protected function resolveDisplayColumn(string $modelClass): string
    {
        try {
            /** @var \Illuminate\Database\Eloquent\Model $instance */
            $instance = new $modelClass;
            $table = $instance->getTable();

            foreach (['name', 'full_name', 'title', 'reference'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    return $column;
                }
            }
        } catch (\Throwable) {
            //
        }

        return 'id';
    }
}
