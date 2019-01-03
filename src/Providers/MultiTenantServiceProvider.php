<?php

namespace EJLab\Laravel\MultiTenant\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use EJLab\Laravel\MultiTenant\Commands\TenantSetupCommand;
use EJLab\Laravel\MultiTenant\Commands\TenantDestroyCommand;
use EJLab\Laravel\MultiTenant\Commands\ModelMakeCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateInstallCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateMakeCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateRefreshCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateResetCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateRollbackCommand;
use EJLab\Laravel\MultiTenant\Commands\Migrate\MigrateStatusCommand;
use EJLab\Laravel\MultiTenant\Commands\Seeds\SeedCommand;
use EJLab\Laravel\MultiTenant\Commands\Seeds\SeederMakeCommand;

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
            __DIR__.'/../../config/elmt.php' => config_path('elmt.php'),
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
            __DIR__.'/../../database/Tenant.php' => app_path('Models/System/Tenant.php'),
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
            TenantSetupCommand::class,
            TenantDestroyCommand::class,
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

        $this->app->extend('command.migrate.refresh', function ($object, $app) {
            return new MigrateRefreshCommand;
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

        $this->app->extend('command.seed', function ($object, $app) {
            return new SeedCommand($app['db']);
        });

        $this->app->extend('command.seeder.make', function ($object, $app) {
            return new SeederMakeCommand($app['files'], $app['composer']);
        });

        $this->app->extend('command.model.make', function ($object, $app) {
            return new ModelMakeCommand($app['files']);
        });
    }
}
