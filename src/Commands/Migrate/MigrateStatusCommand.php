<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use EJLab\Laravel\MultiTenant\DatabaseManager;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Symfony\Component\Console\Input\InputOption;

class MigrateStatusCommand extends StatusCommand
{
    use TenantCommandTrait;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator $migrator
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
            $this->setTenantDatabase();
            foreach ($tenants as $tenant) {
                $this->manager->setConnection($tenant);
                $this->info("Migration status for '{$tenant->name}'");
                parent::handle();
                $this->info('');
            }
        } else {
            $this->setSystemDatabase();
            parent::handle();
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge([
            ['tenant', 'T', InputOption::VALUE_NONE, "Show the status of each migration for tenant database. '--database' option will be ignored. use '--domain' instead."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ], parent::getOptions());
    }
}
