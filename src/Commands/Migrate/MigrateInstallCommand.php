<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use App\Tenant;
use DB;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Symfony\Component\Console\Input\InputOption;

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
            
            $manager = new DatabaseManager();
            DB::setDefaultConnection($manager->systemConnectionName);
            
            if ($domain == 'all') $tenants = Tenant::all();
            else $tenants = Tenant::where('domain', $domain)->get();

            $drawBar = (count($tenants) > 1);

            if ($drawBar) $bar = $this->output->createProgressBar(count($tenants));

            foreach ($tenants as $tenant) {
                $manager->setConnection($tenant);
                $this->repository->setSource($manager->tenantConnectionName);
                $this->repository->createRepository();
                if ($drawBar) $bar->advance();
                $this->info(($drawBar?'  ':'')."Migration for '{$tenant->name}' created successfully.");
            }

            if ($drawBar) $bar->finish();

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
