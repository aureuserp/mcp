<?php

namespace Webkul\Mcp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Throwable;
use Webkul\Mcp\Support\SetupConfigurator;

class SetupCommand extends Command
{
    protected $signature = 'mcp:setup';

    protected $description = 'Setup MCP OAuth, CORS, and plugin auth wiring for AureusERP MCP plugin.';

    public function __construct(protected SetupConfigurator $setupConfigurator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! class_exists(Passport::class)) {
            $this->error('Laravel Passport is not installed. Run: composer require laravel/passport:^12.0');

            return self::FAILURE;
        }

        if (! is_file(config_path('cors.php'))) {
            $this->info('Publishing CORS config...');
            $this->call('config:publish', [
                'name' => 'cors',
            ]);
        } else {
            $this->line('CORS config already exists, skipping publish.');
        }

        $this->info('Publishing MCP OAuth view...');
        $this->call('vendor:publish', [
            '--tag'   => 'mcp-views',
        ]);

        if (! Schema::hasTable('oauth_auth_codes')) {
            $this->info('Ensuring Passport installation scaffolding...');

            $this->call('passport:install', [
                '--no-interaction' => true,
            ]);
        }

        $this->line('Ensuring auth provider/guard config...');
        $authUpdated = $this->setupConfigurator->ensureAuthConfig();

        $this->line('Ensuring CORS paths and origin config...');
        $corsUpdated = $this->setupConfigurator->ensureCorsConfig();

        $this->line('Clearing caches...');
        try {
            $this->call('optimize:clear');
        } catch (Throwable) {
            $this->warn('optimize:clear failed. Falling back to config:clear and route:clear.');
            $this->call('config:clear');
            $this->call('route:clear');
        }

        $this->newLine();
        $this->info('MCP setup completed.');
        $this->line('- auth.php updated: '.($authUpdated ? 'yes' : 'no changes'));
        $this->line('- cors.php updated: '.($corsUpdated ? 'yes' : 'no changes'));
        $this->newLine();
        $this->comment('If CORS still fails, set CORS_ALLOWED_ORIGINS in .env to your Inspector origin and run: php artisan optimize:clear');

        return self::SUCCESS;
    }
}
