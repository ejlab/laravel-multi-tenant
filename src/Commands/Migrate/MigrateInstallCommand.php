<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use Illuminate\Database\Console\Migrations\InstallCommand;
use Symfony\Component\Console\Input\InputOption;
use App\Tenant;

class MigrateInstallCommand extends InstallCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->input->getOption('tenant')) {

            $domain = $this->input->getOption('domain') ?: 'all';
            
            $tenants = NULL;
            if ($domain == 'all') $tenants = Tenant::all();
            else $tenants = Tenant::where('domain', $domain)->get();

            foreach ($tenants as $tenant) {
                // todo: set connection
                $this->repository->setSource('tenant');
                $this->repository->createRepository();
                $this->info("Migration table for {$tenant->name} created successfully.");
            }
        } else parent::fire();
    }

    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['tenant', 'T', InputOption::VALUE_NONE, "Create the migration repository for tenant database. '--database' option will be ignored. use '--domain' instead."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ]);
    }
}
