<?php

use App\Models\User;
use Webkul\Mcp\Servers\DevAgentServer;
use Webkul\Mcp\Servers\ErpAgentServer;
use Webkul\Mcp\Tools\Admin\Business\Accounts\InvoiceListTool;
use Webkul\Mcp\Tools\Admin\Business\Sales\SalesPipelineSummaryTool;
use Webkul\Mcp\Tools\Dev\ListPluginsTool;
use Webkul\Mcp\Tools\Dev\PluginSummaryTool;

it('returns plugin list from the developer server tool', function () {
    $user = User::factory()->create();

    $response = DevAgentServer::actingAs($user)->tool(ListPluginsTool::class, [
        'query' => 'mcp',
        'limit' => 10,
    ]);

    $response
        ->assertOk()
        ->assertSee('plugins');
});

it('returns plugin summary error for unknown plugin', function () {
    $user = User::factory()->create();

    $response = DevAgentServer::actingAs($user)->tool(PluginSummaryTool::class, [
        'plugin' => 'plugin-that-does-not-exist',
    ]);

    $response->assertHasErrors();
});

it('returns invoice list payload for erp agent server', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(InvoiceListTool::class);

    $response
        ->assertOk()
        ->assertSee('invoice_list');
});

it('returns sales pipeline payload for erp agent server', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(SalesPipelineSummaryTool::class);

    $response
        ->assertOk()
        ->assertSee('sales_pipeline_summary');
});
