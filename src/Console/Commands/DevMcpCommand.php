<?php

namespace Webkul\Mcp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('mcp:dev', 'Start the AureusERP Dev MCP server (for use in .mcp.json / mcp.json)')]
class DevMcpCommand extends Command
{
    public function handle(): int
    {
        return Artisan::call('mcp:start aureuserp-dev');
    }
}
