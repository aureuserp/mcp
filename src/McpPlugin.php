<?php

namespace Webkul\Mcp;

use Filament\Contracts\Plugin;
use Filament\Panel;

class McpPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mcp';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
