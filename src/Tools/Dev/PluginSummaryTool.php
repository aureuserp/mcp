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
class PluginSummaryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Fetch technical details for a specific ERP plugin including dependency graph and installation state.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'plugin' => ['required', 'string', 'max:100'],
        ], [
            'plugin.required' => 'Provide a plugin name, for example: "accounts" or "mcp".',
        ]);

        if (! Schema::hasTable('plugins')) {
            return Response::error('The plugins table is not available. Run core migrations first.');
        }

        $plugin = Plugin::query()
            ->with(['dependencies:id,name', 'dependents:id,name'])
            ->where('name', $validated['plugin'])
            ->first();

        if (! $plugin) {
            return Response::error("Plugin [{$validated['plugin']}] was not found.");
        }

        return Response::json([
            'name'                => $plugin->name,
            'author'              => $plugin->author,
            'summary'             => $plugin->summary,
            'latest_version'      => $plugin->latest_version,
            'is_active'           => (bool) $plugin->is_active,
            'is_installed'        => (bool) $plugin->is_installed,
            'dependencies'        => $plugin->dependencies->pluck('name')->values()->all(),
            'dependents'          => $plugin->dependents->pluck('name')->values()->all(),
            'config_dependencies' => $plugin->getDependenciesFromConfig(),
            'config_dependents'   => $plugin->getDependentsFromConfig(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'plugin' => $schema->string()
                ->description('Plugin name registered in Plugin Manager, such as "mcp" or "projects".')
                ->required(),
        ];
    }
}
