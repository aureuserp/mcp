<?php

namespace Webkul\Mcp\Tools\Admin\Business;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\Mcp\Support\BusinessToolService;

#[IsReadOnly]
#[IsIdempotent]
abstract class BusinessMetricTool extends Tool
{
    public function __construct(protected BusinessToolService $businessToolService) {}

    abstract protected function metric(): string;

    public function handle(Request $request): Response
    {
        $payload = $this->businessToolService->run($this->metric());

        if (isset($payload['error'])) {
            return Response::error((string) $payload['error']);
        }

        return Response::json([
            'metric' => $this->metric(),
            'data'   => $payload,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
