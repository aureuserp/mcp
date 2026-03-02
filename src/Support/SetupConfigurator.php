<?php

namespace Webkul\Mcp\Support;

class SetupConfigurator
{
    public function ensureCorsConfig(): bool
    {
        $path = config_path('cors.php');

        if (! is_file($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        $updated = $contents;

        $updated = preg_replace(
            "/'paths'\s*=>\s*\[[\s\S]*?\],/m",
            "'paths' => [\n        'api/*',\n        'mcp/*',\n        'oauth/*',\n        '.well-known/*',\n        'sanctum/csrf-cookie',\n    ],",
            $updated,
            1
        ) ?? $updated;

        $updated = preg_replace(
            "/'allowed_origins'\s*=>\s*\[[\s\S]*?\],/m",
            "'allowed_origins' => array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*')))),",
            $updated,
            1
        ) ?? $updated;

        $updated = preg_replace(
            "/'supports_credentials'\s*=>\s*[^\n\r]+/",
            "'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),",
            $updated,
            1
        ) ?? $updated;

        if ($updated === $contents) {
            return false;
        }

        file_put_contents($path, $updated);

        return true;
    }

    public function ensureAuthConfig(): bool
    {
        $path = config_path('auth.php');

        if (! is_file($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        $updated = $contents;

        if (! str_contains($updated, "'api' => [")) {
            $updated = preg_replace(
                "/'guards'\s*=>\s*\[\s*('web'\s*=>\s*\[[\s\S]*?\],)/",
                "'guards' => [\n        $1\n        'api' => [\n            'driver'   => 'passport',\n            'provider' => 'mcp_users',\n        ],",
                $updated,
                1
            ) ?? $updated;
        }

        if (! str_contains($updated, "'mcp_users' => [")) {
            $updated = preg_replace(
                "/'providers'\s*=>\s*\[\s*('users'\s*=>\s*\[[\s\S]*?\],)/",
                "'providers' => [\n        $1\n        'mcp_users' => [\n            'driver' => 'eloquent',\n            'model'  => env('MCP_AUTH_MODEL', Webkul\\Mcp\\Models\\McpOAuthUser::class),\n        ],",
                $updated,
                1
            ) ?? $updated;
        } else {
            $updated = preg_replace(
                "/'model'\s*=>\s*env\('MCP_AUTH_MODEL',[^\)]*\),/",
                "'model'  => env('MCP_AUTH_MODEL', Webkul\\Mcp\\Models\\McpOAuthUser::class),",
                $updated,
                1
            ) ?? $updated;
        }

        if ($updated === $contents) {
            return false;
        }

        file_put_contents($path, $updated);

        return true;
    }
}
