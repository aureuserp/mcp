<?php

namespace Webkul\Mcp\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\PluginManager\Models\Plugin;

#[IsReadOnly]
#[IsIdempotent]
class ListPluginsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List ERP plugins with optional name filtering and activation status.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'query'       => ['nullable', 'string', 'max:100'],
            'active_only' => ['nullable', 'boolean'],
            'limit'       => ['nullable', 'integer', 'min:1', 'max:300'],
        ]);

        if (! Schema::hasTable('plugins')) {
            return Response::error('Plugin metadata table is unavailable.');
        }

        $query = Plugin::query()->orderBy('name');

        if (! empty($validated['query'])) {
            $query->where('name', 'like', '%'.$validated['query'].'%');
        }

        if ((bool) ($validated['active_only'] ?? false)) {
            $query->where('is_active', true);
        }

        $limit = (int) ($validated['limit'] ?? 100);
        $plugins = $query->limit($limit)->get();

        return Response::json([
            'count'   => $plugins->count(),
            'plugins' => $plugins->map(function (Plugin $plugin): array {
                return [
                    'name'           => $plugin->name,
                    'is_active'      => (bool) $plugin->is_active,
                    'latest_version' => $plugin->latest_version,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional plugin name filter.'),
            'active_only' => $schema->boolean()
                ->description('Only include active plugins.')
                ->default(false),
            'limit' => $schema->integer()
                ->description('Maximum records to return.')
                ->default(100),
        ];
    }
}
