<?php

namespace Webkul\Mcp\Prompts\Dev;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class DevCodingPrompt extends Prompt
{
    protected string $description = <<<'MARKDOWN'
        Build a developer-oriented prompt payload for coding tasks in AureusERP.
    MARKDOWN;

    /**
     * @return array<int, \Laravel\Mcp\Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'task'   => ['required', 'string', 'max:500'],
            'plugin' => ['nullable', 'string', 'max:100'],
        ]);

        $plugin = (string) ($validated['plugin'] ?? 'core application');

        return [
            Response::text('You are a senior Laravel engineer for AureusERP. Return actionable implementation steps and code-focused output.')->asAssistant(),
            Response::text("Task: {$validated['task']}\nTarget plugin: {$plugin}"),
        ];
    }

    /**
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'task',
                description: 'Coding task to implement.',
                required: true
            ),
            new Argument(
                name: 'plugin',
                description: 'Optional plugin/module name.',
                required: false
            ),
        ];
    }
}
