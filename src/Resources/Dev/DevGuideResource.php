<?php

namespace Webkul\Mcp\Resources\Dev;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class DevGuideResource extends Resource
{
    protected string $description = <<<'MARKDOWN'
        Developer-facing guidance for building features in AureusERP plugins.
    MARKDOWN;

    protected string $uri = 'erp://mcp/dev-guide';

    protected string $mimeType = 'application/json';

    public function handle(Request $request): Response
    {
        return Response::json([
            'audience'   => 'developers',
            'principles' => [
                'Follow plugin-first architecture for modular features.',
                'Use plugin install commands for lifecycle and permissions.',
                'Prefer Pest feature tests for behavior verification.',
                'Use OAuth2 bearer token validation and policy checks on sensitive routes.',
            ],
            'quick_commands' => [
                'php artisan list --raw | rg mcp',
                'php artisan mcp:inspector mcp/dev',
                'php artisan test --compact',
            ],
        ]);
    }
}
