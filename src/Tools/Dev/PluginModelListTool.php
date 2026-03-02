<?php

namespace Webkul\Mcp\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

#[IsReadOnly]
#[IsIdempotent]
class PluginModelListTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all Eloquent models across AureusERP plugins. Returns the model class, database table,
        fillable fields, casts, relationships, and whether soft deletes are enabled.
        Optionally filter by plugin folder name (e.g. "sales", "accounts").
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'plugin' => ['nullable', 'string', 'max:100'],
        ]);

        $pluginFilter = $validated['plugin'] ?? null;
        $baseDir = base_path('plugins/webkul');

        if (! is_dir($baseDir)) {
            return Response::error('Plugin directory not found.');
        }

        $result = [];

        foreach (glob($baseDir.'/*/src/Models', GLOB_ONLYDIR) ?: [] as $modelsDir) {
            $pluginName = basename(dirname(dirname($modelsDir)));

            if ($pluginFilter && strtolower($pluginName) !== strtolower($pluginFilter)) {
                continue;
            }

            $namespace = $this->resolveNamespace(dirname(dirname($modelsDir)));
            $models = [];

            foreach (glob($modelsDir.'/*.php') ?: [] as $file) {
                $className = basename($file, '.php');
                $fqn = $namespace.'\\Models\\'.$className;

                if (! class_exists($fqn)) {
                    $models[] = ['class' => $fqn, 'status' => 'not_autoloaded'];

                    continue;
                }

                try {
                    /** @var \Illuminate\Database\Eloquent\Model $instance */
                    $instance = new $fqn;
                    $reflection = new ReflectionClass($fqn);
                    $relationships = [];

                    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                        if ($method->class !== $fqn || $method->getNumberOfParameters() > 0) {
                            continue;
                        }

                        $returnType = (string) ($method->getReturnType() ?? '');

                        if (str_contains($returnType, 'Illuminate\\Database\\Eloquent\\Relations\\')) {
                            $relationships[] = $method->getName();
                        }
                    }

                    $models[] = [
                        'class'         => $fqn,
                        'table'         => $instance->getTable(),
                        'fillable'      => $instance->getFillable(),
                        'casts'         => array_keys($instance->getCasts()),
                        'relationships' => $relationships,
                        'soft_deletes'  => in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive($fqn)),
                    ];
                } catch (Throwable $e) {
                    $models[] = ['class' => $fqn, 'error' => $e->getMessage()];
                }
            }

            if (! empty($models)) {
                $result[] = [
                    'plugin' => $pluginName,
                    'count'  => count($models),
                    'models' => $models,
                ];
            }
        }

        return Response::json(['plugins' => $result]);
    }

    private function resolveNamespace(string $pluginRoot): string
    {
        $composerPath = $pluginRoot.'/composer.json';

        if (! is_file($composerPath)) {
            return 'Webkul\\Unknown';
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($composerPath), true);

        /** @var array<string, string> $autoload */
        $autoload = $composer['autoload']['psr-4'] ?? [];

        foreach ($autoload as $namespace => $path) {
            if (rtrim($path, '/') === 'src') {
                return rtrim($namespace, '\\');
            }
        }

        return 'Webkul\\Unknown';
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'plugin' => $schema->string()
                ->description('Plugin folder name to filter on, e.g. "sales", "accounts", "projects". Omit to list all plugins.'),
        ];
    }
}
