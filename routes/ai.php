<?php

use Laravel\Mcp\Facades\Mcp;
use Webkul\Mcp\Servers\DevAgentServer;
use Webkul\Mcp\Servers\ErpAgentServer;
use Webkul\PluginManager\Package;

if (Package::isPluginInstalled('mcp')) {
    Mcp::oauthRoutes();

    Mcp::local('aureuserp-dev', DevAgentServer::class);

    Mcp::web('/mcp/dev', DevAgentServer::class)
        ->middleware(['auth:api']);

    Mcp::web('/mcp/erp', ErpAgentServer::class)
        ->middleware(['auth:api']);
}
