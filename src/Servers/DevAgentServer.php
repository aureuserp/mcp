<?php

namespace Webkul\Mcp\Servers;

use Laravel\Mcp\Server;
use Webkul\Mcp\Prompts\Dev\DevCodingPrompt;
use Webkul\Mcp\Resources\Dev\DevGuideResource;
use Webkul\Mcp\Tools\Dev\ApiResourceListTool;
use Webkul\Mcp\Tools\Dev\FilamentResourceListTool;
use Webkul\Mcp\Tools\Dev\ListPluginsTool;
use Webkul\Mcp\Tools\Dev\PluginModelListTool;
use Webkul\Mcp\Tools\Dev\PluginSummaryTool;
use Webkul\Mcp\Tools\Dev\RouteListTool;
use Webkul\Mcp\Tools\Dev\SearchDocsTool;

class DevAgentServer extends Server
{
    protected string $name = 'Aureus ERP Dev Agent';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        This MCP server is for developers and coding agents building features in AureusERP.
        - Always call search-docs-tool FIRST before writing any plugin or feature code.
        - Use route-list-tool to discover existing API endpoints before creating new ones.
        - Use plugin-model-list-tool to inspect fillable, casts, and relationships on Eloquent models.
        - Use filament-resource-list-tool to understand existing admin UI before adding new resources.
        - Use api-resource-list-tool to discover existing JSON API resource classes by plugin and version.
        - Prefer plugin-first architecture for all new features.
    MARKDOWN;

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        SearchDocsTool::class,
        ListPluginsTool::class,
        PluginSummaryTool::class,
        RouteListTool::class,
        FilamentResourceListTool::class,
        PluginModelListTool::class,
        ApiResourceListTool::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        DevGuideResource::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        DevCodingPrompt::class,
    ];
}
