<?php

namespace Webkul\Mcp\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[IsReadOnly]
#[IsIdempotent]
class SearchDocsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Search the official AureusERP developer documentation at devdocs.aureuserp.com.
        Pass one or more queries to find relevant documentation on plugin development,
        architecture, models, migrations, Filament resources, policies, settings, and more.
        Always use this tool before writing any plugin or feature code.
    MARKDOWN;

    protected string $baseUrl = 'https://devdocs.aureuserp.com/master';

    /**
     * All known documentation pages with associated keywords for relevance scoring.
     *
     * @var array<string, array{path: string, keywords: string[]}>
     */
    protected array $index = [
        'prologue/introduction' => [
            'path'     => 'prologue/introduction.html',
            'keywords' => ['introduction', 'about', 'overview', 'erp', 'aureus', 'features', 'what is'],
        ],
        'prologue/upgrade-guide' => [
            'path'     => 'prologue/upgrade-guide.html',
            'keywords' => ['upgrade', 'update', 'breaking changes', 'version', 'changelog'],
        ],
        'prologue/contribution-guide' => [
            'path'     => 'prologue/contribution-guide.html',
            'keywords' => ['contribution', 'contribute', 'pull request', 'pr', 'fork', 'open source'],
        ],
        'installation/introduction' => [
            'path'     => 'installation/introduction.html',
            'keywords' => ['install', 'installation', 'setup', 'begin', 'start'],
        ],
        'installation/requirements' => [
            'path'     => 'installation/requirements.html',
            'keywords' => ['requirements', 'prerequisites', 'php version', 'system', 'dependencies'],
        ],
        'installation/installation' => [
            'path'     => 'installation/installation.html',
            'keywords' => ['install', 'composer', 'artisan', 'env', 'configure', 'setup'],
        ],
        'installation/docker' => [
            'path'     => 'installation/docker.html',
            'keywords' => ['docker', 'container', 'sail', 'docker compose', 'dockerize'],
        ],
        'architecture/introduction' => [
            'path'     => 'architecture/introduction.html',
            'keywords' => ['architecture', 'design', 'structure', 'concepts', 'overview'],
        ],
        'architecture/plugins' => [
            'path'     => 'architecture/plugins.html',
            'keywords' => ['plugin', 'module', 'package', 'plugin system', 'plugin architecture', 'structure'],
        ],
        'architecture/frontend' => [
            'path'     => 'architecture/frontend.html',
            'keywords' => ['frontend', 'vite', 'tailwind', 'css', 'livewire', 'alpine', 'js'],
        ],
        'architecture/panels' => [
            'path'     => 'architecture/panels.html',
            'keywords' => ['panel', 'admin panel', 'filament panel', 'customer panel', 'panel architecture'],
        ],
        'getting-started/migrations' => [
            'path'     => 'getting-started/migrations.html',
            'keywords' => ['migration', 'database', 'schema', 'table', 'column', 'alter', 'create table'],
        ],
        'getting-started/settings' => [
            'path'     => 'getting-started/settings.html',
            'keywords' => ['settings', 'configuration', 'spatie settings', 'config', 'app settings'],
        ],
        'getting-started/models' => [
            'path'     => 'getting-started/models.html',
            'keywords' => ['model', 'eloquent', 'fillable', 'casts', 'relationships', 'model class'],
        ],
        'getting-started/policies' => [
            'path'     => 'getting-started/policies.html',
            'keywords' => ['policy', 'authorization', 'gate', 'permission', 'acl', 'access control'],
        ],
        'getting-started/seeders' => [
            'path'     => 'getting-started/seeders.html',
            'keywords' => ['seeder', 'seed', 'factory', 'database seeding', 'fake data'],
        ],
        'getting-started/clusters' => [
            'path'     => 'getting-started/clusters.html',
            'keywords' => ['cluster', 'filament cluster', 'navigation group', 'grouping resources'],
        ],
        'getting-started/pages' => [
            'path'     => 'getting-started/pages.html',
            'keywords' => ['page', 'filament page', 'custom page', 'static page'],
        ],
        'plugins/introduction' => [
            'path'     => 'plugins/introduction.html',
            'keywords' => ['plugin', 'create plugin', 'new plugin', 'plugin development', 'plugin intro', 'build plugin'],
        ],
        'plugins/service-provider' => [
            'path'     => 'plugins/service-provider.html',
            'keywords' => ['service provider', 'provider', 'boot', 'register', 'binding', 'ioc'],
        ],
        'plugins/plugin' => [
            'path'     => 'plugins/plugin.html',
            'keywords' => ['plugin class', 'plugin file', 'plugin registration', 'plugin.php', 'plugin entry'],
        ],
        'plugins/database' => [
            'path'     => 'plugins/database.html',
            'keywords' => ['database', 'migration', 'plugin migration', 'plugin database', 'schema'],
        ],
        'plugins/resources' => [
            'path'     => 'plugins/resources.html',
            'keywords' => ['filament resource', 'crud', 'resource class', 'list records', 'create records', 'edit records', 'view records'],
        ],
        'plugins/models' => [
            'path'     => 'plugins/models.html',
            'keywords' => ['model', 'plugin model', 'eloquent model', 'model class', 'plugin eloquent'],
        ],
        'plugins/policies' => [
            'path'     => 'plugins/policies.html',
            'keywords' => ['policy', 'plugin policy', 'authorization', 'permission', 'gate', 'plugin auth'],
        ],
        'plugins/filament' => [
            'path'     => 'plugins/filament.html',
            'keywords' => ['filament', 'plugin filament', 'filament integration', 'filament setup', 'filament plugin'],
        ],
        'plugins/clusters' => [
            'path'     => 'plugins/clusters.html',
            'keywords' => ['cluster', 'plugin cluster', 'navigation', 'grouping', 'sidebar navigation'],
        ],
        'advanced/dashboard' => [
            'path'     => 'advanced/dashboard.html',
            'keywords' => ['dashboard', 'widget', 'stats', 'chart', 'overview', 'dashboard widget'],
        ],
        'advanced/customer-account' => [
            'path'     => 'advanced/customer-account.html',
            'keywords' => ['customer', 'customer account', 'customer panel', 'customer portal', 'portal'],
        ],
        'advanced/table-views' => [
            'path'     => 'advanced/table-views.html',
            'keywords' => ['table view', 'saved filter', 'table preset', 'view preset', 'saved view'],
        ],
        'advanced/progress-stepper' => [
            'path'     => 'advanced/progress-stepper.html',
            'keywords' => ['progress stepper', 'stepper', 'step', 'wizard', 'multi step', 'progress bar'],
        ],
        'advanced/custom-fields' => [
            'path'     => 'advanced/custom-fields.html',
            'keywords' => ['custom field', 'dynamic field', 'extra field', 'field builder', 'user defined field'],
        ],
    ];

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'queries'     => ['required', 'array', 'min:1'],
            'queries.*'   => ['required', 'string', 'max:200'],
            'token_limit' => ['nullable', 'integer', 'min:100', 'max:100000'],
        ]);

        $queries = array_filter(array_map('trim', (array) $validated['queries']));
        $tokenLimit = (int) ($validated['token_limit'] ?? 4000);
        $charLimit = $tokenLimit * 4; // ~4 chars per token

        $topSlugs = $this->rankPages($queries);

        if (empty($topSlugs)) {
            $available = implode(', ', array_keys($this->index));

            return Response::error(
                'No documentation pages matched your queries: ['.implode(', ', $queries)."]. \n"
                ."Available topics: {$available}"
            );
        }

        $output = [];
        $charsUsed = 0;

        foreach ($topSlugs as $slug) {
            $url = $this->baseUrl.'/'.$this->index[$slug]['path'];

            try {
                $response = Http::timeout(10)->get($url);

                if (! $response->successful()) {
                    $output[] = "<!-- Could not fetch `{$slug}` ({$response->status()}) -->";

                    continue;
                }

                $content = $this->extractContent($response->body(), $slug, $url);

                if ($charsUsed + strlen($content) > $charLimit) {
                    $output[] = substr($content, 0, $charLimit - $charsUsed);
                    break;
                }

                $output[] = $content;
                $charsUsed += strlen($content);
            } catch (Throwable $e) {
                $output[] = "<!-- Failed to fetch `{$slug}`: {$e->getMessage()} -->";
            }
        }

        return Response::text(implode("\n\n---\n\n", $output));
    }

    /**
     * Score and rank pages by how well they match the given queries.
     * Returns up to 3 most relevant page slugs.
     *
     * @param  string[]  $queries
     * @return string[]
     */
    private function rankPages(array $queries): array
    {
        $scores = [];

        foreach ($this->index as $slug => $page) {
            $score = 0;

            foreach ($queries as $query) {
                $queryLower = strtolower($query);

                // Slug match gets highest priority
                if (str_contains($slug, str_replace(' ', '-', $queryLower))) {
                    $score += 10;
                }

                foreach ($page['keywords'] as $keyword) {
                    if (str_contains($queryLower, $keyword) || str_contains($keyword, $queryLower)) {
                        $score += 3;
                    } else {
                        // Partial word match
                        foreach (explode(' ', $queryLower) as $word) {
                            if (strlen($word) > 2 && str_contains($keyword, $word)) {
                                $score += 1;
                            }
                        }
                    }
                }
            }

            if ($score > 0) {
                $scores[$slug] = $score;
            }
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, 3);
    }

    /**
     * Extract and convert the main page content from HTML to readable markdown-like text.
     */
    private function extractContent(string $html, string $slug, string $url): string
    {
        // Extract main content area
        if (preg_match('/<main[^>]*>(.*?)<\/main>/si', $html, $m)) {
            $html = $m[1];
        } elseif (preg_match('/<article[^>]*>(.*?)<\/article>/si', $html, $m)) {
            $html = $m[1];
        }

        // Remove non-content blocks
        $html = preg_replace('/<(script|style|nav|aside|footer|head|button)[^>]*>.*?<\/\1>/si', '', $html) ?? $html;

        // Convert headings and common elements to markdown
        $html = preg_replace('/<h1[^>]*>(.*?)<\/h1>/si', "\n# $1\n", $html) ?? $html;
        $html = preg_replace('/<h2[^>]*>(.*?)<\/h2>/si', "\n## $1\n", $html) ?? $html;
        $html = preg_replace('/<h3[^>]*>(.*?)<\/h3>/si', "\n### $1\n", $html) ?? $html;
        $html = preg_replace('/<h4[^>]*>(.*?)<\/h4>/si', "\n#### $1\n", $html) ?? $html;
        $html = preg_replace('/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/si', "\n```\n$1\n```\n", $html) ?? $html;
        $html = preg_replace('/<code[^>]*>(.*?)<\/code>/si', '`$1`', $html) ?? $html;
        $html = preg_replace('/<li[^>]*>(.*?)<\/li>/si', "\n- $1", $html) ?? $html;
        $html = preg_replace('/<p[^>]*>(.*?)<\/p>/si', "\n$1\n", $html) ?? $html;
        $html = preg_replace('/<br[^>\/]*\/?>/si', "\n", $html) ?? $html;

        $text = strip_tags($html);

        // Normalise whitespace
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        return "## Source: [{$slug}]({$url})\n\n{$text}";
    }

    /**
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'queries' => $schema->array()
                ->items($schema->string()->description('Search query, e.g. "create plugin", "filament resource", "model fillable"'))
                ->description('One or more search queries. Pass multiple to broaden results.')
                ->required(),
            'token_limit' => $schema->integer()
                ->description('Approximate max tokens to return. Defaults to 4000. Increase if results are truncated.')
                ->default(4000),
        ];
    }
}
