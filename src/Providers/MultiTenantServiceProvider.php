<?php

namespace EJLab\Laravel\MultiTenant\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateInstallCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateMakeCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateResetCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateRollbackCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateStatusCommand;

class MultiTenantServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/elmt.php' => config_path('elmt.php'),
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
            __DIR__.'/../../database/Tenant.php' => app_path('Tenant.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            // MigrateCommand::class,
        ]);

        $this->app->extend('command.migrate', function ($object, $app) {
            return new MigrateCommand($app['migrator']);
        });

        $this->app->extend('command.migrate.install', function ($object, $app) {
            return new MigrateInstallCommand($app['migration.repository']);
        });
        
        $this->app->extend('command.migrate.make', function ($object, $app) {
            return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
        });

        $this->app->extend('command.migrate.reset', function ($object, $app) {
            return new MigrateResetCommand($app['migrator']);
        });

        $this->app->extend('command.migrate.rollback', function ($object, $app) {
            return new MigrateRollbackCommand($app['migrator']);
        });

        $this->app->extend('command.migrate.status', function ($object, $app) {
            return new MigrateStatusCommand($app['migrator']);
        });
    }
}
