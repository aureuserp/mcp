<?php

namespace Webkul\Mcp\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class RouteListTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List registered application routes. Filter by plugin namespace (e.g. "Sale"), HTTP method,
        or URI prefix (e.g. "admin/api/v1/sales"). Useful for REST API development and navigation.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'plugin' => ['nullable', 'string', 'max:100'],
            'method' => ['nullable', 'string', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'prefix' => ['nullable', 'string', 'max:200'],
            'limit'  => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $pluginFilter = isset($validated['plugin']) ? strtolower((string) $validated['plugin']) : null;
        $methodFilter = isset($validated['method']) ? strtoupper((string) $validated['method']) : null;
        $prefixFilter = isset($validated['prefix']) ? ltrim((string) $validated['prefix'], '/') : null;
        $limit = (int) ($validated['limit'] ?? 200);

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route) use ($pluginFilter, $methodFilter, $prefixFilter): bool {
                $action = $route->getActionName();

                if ($pluginFilter && ! str_contains(strtolower($action), 'webkul\\'.$pluginFilter)) {
                    return false;
                }

                if ($methodFilter && ! in_array($methodFilter, $route->methods())) {
                    return false;
                }

                if ($prefixFilter && ! str_starts_with($route->uri(), $prefixFilter)) {
                    return false;
                }

                return true;
            })
            ->take($limit)
            ->values()
            ->map(function ($route): array {
                return [
                    'methods'    => $route->methods(),
                    'uri'        => $route->uri(),
                    'name'       => $route->getName(),
                    'action'     => $route->getActionName(),
                    'middleware' => $route->middleware(),
                ];
            })
            ->all();

        return Response::json([
            'count'  => count($routes),
            'routes' => $routes,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'plugin' => $schema->string()
                ->description('Filter by plugin namespace keyword, e.g. "Sale", "Account", "Inventory".'),
            'method' => $schema->string()
                ->description('Filter by HTTP method.')
                ->enum(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS']),
            'prefix' => $schema->string()
                ->description('Filter by URI prefix, e.g. "admin/api/v1/sales".'),
            'limit' => $schema->integer()
                ->description('Maximum routes to return.')
                ->default(200),
        ];
    }
}
