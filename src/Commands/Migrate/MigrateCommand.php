<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use EJLab\Laravel\MultiTenant\DatabaseManager;
use Illuminate\Database\Console\Migrations\MigrateCommand as BaseMigrateCommand;

class MigrateCommand extends BaseMigrateCommand
{
    use TenantCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "migrate 
                {--T|tenant : Run migrations for tenant. '--database' option will be ignored. use '--domain' instead.}
                {--domain= : The domain for tenant. 'all' or null value for all tenants.}
                {--database= : The database connection to use}
                {--force : Force the operation to run when in production}
                {--path=* : The path(s) to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--pretend : Dump the SQL queries that would be run}
                {--seed : Indicates if the seed task should be re-run}
                {--step : Force the migrations to be run so they can be rolled back individually}";

    protected $manager;

    /**
     * Create a new migration command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct(\Illuminate\Database\Migrations\Migrator $migrator)
    {
        parent::__construct($migrator);

        $this->manager = new DatabaseManager();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->option('tenant')) {
            $tenants = $this->getTenants();
            $progressBar = $this->output->createProgressBar(count($tenants));
            $this->setTenantDatabase();
            foreach ($tenants as $tenant) {
                $this->manager->setConnection($tenant);
                $this->info("Migrating for '{$tenant->name}'...");
                $progressBar->advance();
                parent::handle();
            }
        } else {
            $this->setSystemDatabase();
            parent::handle();
        }
    }
}
