<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use App\Models\System\Tenant;
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
    public function handle()
    {
        $manager = new DatabaseManager();
        DB::setDefaultConnection($manager->systemConnectionName);
        
        if ($this->input->getOption('tenant')) {

            $domain = $this->input->getOption('domain') ?: 'all';
            if ($domain == 'all') $tenants = Tenant::all();
            else $tenants = Tenant::where('domain', $domain)->get();

            $drawBar = (count($tenants) > 1);

            if ($drawBar) $bar = $this->output->createProgressBar(count($tenants));

            foreach ($tenants as $tenant) {
                $manager->setConnection($tenant);
                $this->repository->setSource($manager->tenantConnectionName);
                $this->repository->createRepository();
                if ($drawBar) $bar->advance();
                $this->info(($drawBar?'  ':'')."Migration for '{$tenant->name}' database created successfully.");
            }

            if ($drawBar) $bar->finish();

        } else {
            $this->repository->createRepository();
            $this->info('Migration table for system database created successfully.');
        }
    }

    protected function getOptions()
    {
        return [
            ['tenant', 'T', InputOption::VALUE_NONE, "Create the migration repository for tenant database."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ];
    }
}
