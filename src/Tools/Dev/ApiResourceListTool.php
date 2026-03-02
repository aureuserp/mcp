<?php

namespace Webkul\Mcp\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class ApiResourceListTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all Eloquent API Resource classes (JsonResource) across AureusERP plugins grouped by
        plugin and API version (V1, V2, …). Use this to discover available resource classes before
        writing controllers or understanding the REST API response structure.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'plugin' => ['nullable', 'string', 'max:100'],
        ]);

        $pluginFilter = $validated['plugin'] ?? null;
        $baseDir = base_path('plugins/webkul');

        if (! is_dir($baseDir)) {
            return Response::error('Plugin directory not found.');
        }

        $result = [];

        foreach (glob($baseDir.'/*/src/Http/Resources', GLOB_ONLYDIR) ?: [] as $resourcesDir) {
            $pluginName = basename(dirname(dirname(dirname($resourcesDir))));

            if ($pluginFilter && strtolower($pluginName) !== strtolower($pluginFilter)) {
                continue;
            }

            $resources = [];

            // Root-level resources (unversioned)
            foreach (glob($resourcesDir.'/*.php') ?: [] as $file) {
                $resources[] = [
                    'class'   => basename($file, '.php'),
                    'version' => 'unversioned',
                    'file'    => str_replace(base_path().'/', '', $file),
                ];
            }

            // Versioned subdirectories: V1, V2, …
            foreach (glob($resourcesDir.'/V*', GLOB_ONLYDIR) ?: [] as $versionDir) {
                $version = basename($versionDir);

                foreach (glob($versionDir.'/*.php') ?: [] as $file) {
                    $resources[] = [
                        'class'   => basename($file, '.php'),
                        'version' => $version,
                        'file'    => str_replace(base_path().'/', '', $file),
                    ];
                }
            }

            if (! empty($resources)) {
                $result[] = [
                    'plugin'    => $pluginName,
                    'count'     => count($resources),
                    'resources' => $resources,
                ];
            }
        }

        return Response::json(['plugins' => $result]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'plugin' => $schema->string()
                ->description('Plugin folder name to filter on, e.g. "sales", "accounts". Omit to list all plugins.'),
        ];
    }
}
