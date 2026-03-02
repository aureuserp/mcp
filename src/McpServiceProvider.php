<?php

namespace Webkul\Mcp;

use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Webkul\Mcp\Console\Commands\DevMcpCommand;
use Webkul\Mcp\Console\Commands\SetupCommand;
use Webkul\Mcp\Support\PassportClientRepository;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class McpServiceProvider extends PackageServiceProvider
{
    public static string $name = 'mcp';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasRoute('ai')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->endWith = function (InstallCommand $installCommand): void {
                    $installCommand->call('mcp:setup');
                };
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {})
            ->hasCommands([
                SetupCommand::class,
                DevMcpCommand::class,
            ])
            ->icon('support');
    }

    public function packageRegistered(): void
    {
        if (class_exists(ClientRepository::class)) {
            $this->app->bind(ClientRepository::class, PassportClientRepository::class);
        }

        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(McpPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        if (class_exists(Passport::class)) {
            Passport::authorizationView(function (array $parameters) {
                return view('mcp.authorize', $parameters);
            });
        }

        // Passport's OAuth authorize flow requires a named 'login' route to redirect
        // unauthenticated users. Register a fallback only when no other provider defines it.
        if (! Route::has('login')) {
            Route::redirect('/login', '/admin/login')->name('login');
        }
    }
}
