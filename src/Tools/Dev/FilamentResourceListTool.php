<?php

namespace Webkul\Mcp\Tools\Dev;

use Filament\Facades\Filament;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class FilamentResourceListTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all Filament resources, pages, and widgets registered across application panels (admin, customer).
        Filter by panel ID or plugin namespace to find UI components for a specific domain.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'panel'  => ['nullable', 'string', 'max:100'],
            'plugin' => ['nullable', 'string', 'max:100'],
            'type'   => ['nullable', 'string', 'in:resources,pages,widgets'],
        ]);

        $panelFilter = $validated['panel'] ?? null;
        $pluginFilter = isset($validated['plugin']) ? strtolower((string) $validated['plugin']) : null;
        $typeFilter = $validated['type'] ?? null;

        $result = [];

        foreach (Filament::getPanels() as $panelId => $panel) {
            if ($panelFilter && $panelId !== $panelFilter) {
                continue;
            }

            $panelData = [
                'panel'     => $panelId,
                'resources' => [],
                'pages'     => [],
                'widgets'   => [],
            ];

            $matchesPlugin = function (string $class) use ($pluginFilter): bool {
                return ! $pluginFilter || str_contains(strtolower($class), 'webkul\\'.$pluginFilter);
            };

            if (! $typeFilter || $typeFilter === 'resources') {
                $panelData['resources'] = collect($panel->getResources())
                    ->filter($matchesPlugin)
                    ->values()
                    ->all();
            }

            if (! $typeFilter || $typeFilter === 'pages') {
                $panelData['pages'] = collect($panel->getPages())
                    ->filter($matchesPlugin)
                    ->values()
                    ->all();
            }

            if (! $typeFilter || $typeFilter === 'widgets') {
                $panelData['widgets'] = collect($panel->getWidgets())
                    ->filter($matchesPlugin)
                    ->values()
                    ->all();
            }

            $result[] = $panelData;
        }

        return Response::json(['panels' => $result]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'panel' => $schema->string()
                ->description('Panel ID to inspect, e.g. "admin" or "customer".'),
            'plugin' => $schema->string()
                ->description('Filter by plugin namespace keyword, e.g. "Sale", "Account", "Inventory".'),
            'type' => $schema->string()
                ->description('Limit to a specific component type.')
                ->enum(['resources', 'pages', 'widgets']),
        ];
    }
}
