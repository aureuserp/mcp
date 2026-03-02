<?php

namespace Webkul\Mcp;

use Filament\Panel;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
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

        // Intercept unauthenticated access to any OAuth route and send users directly
        // to the admin login page. A route-based redirect at /login is unreliable because
        // other panels (e.g. customer panel) register their own /login route first and win
        // route matching. Hooking the exception handler bypasses that entirely.
        $handler = app(ExceptionHandler::class);

        if (method_exists($handler, 'renderable')) {
            $handler->renderable(function (AuthenticationException $e, Request $request) {
                if (! $request->expectsJson() && $request->is('oauth/*')) {
                    return redirect()->guest(url('/admin/login'));
                }
            });
        }
    }
}
