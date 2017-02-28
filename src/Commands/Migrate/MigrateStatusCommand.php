<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use App\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Symfony\Component\Console\Input\InputOption;

class MigrateStatusCommand extends StatusCommand
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

            if ($domain == 'all') $tenants = Tenant::all();
            else $tenants = Tenant::where('domain', $domain)->get();

            $drawBar = (count($tenants) > 1);

            foreach ($tenants as $tenant) {
                $manager->setConnection($tenant);
                $this->migrator->setConnection($manager->tenantConnectionName);

                if (! $this->migrator->repositoryExists()) {
                    $this->error("No migrations found for '{$tenant->name}'.");
                    continue;
                }

                $ran = $this->migrator->getRepository()->getRan();

                if (count($migrations = $this->getStatusFor($ran)) > 0) {
                    $this->table(['Ran?', "Migration for '{$tenant->name}'"], $migrations);
                } else {
                    $this->error("No migrations found for '{$tenant->name}'.");
                }
            }

        } else parent::fire();
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        $paths = $this->getMigrationPaths();
        if ($this->input->getOption('tenant')) {
            $paths = array_map(function ($path){
                return $path .= DIRECTORY_SEPARATOR.'tenant';
            }, $paths);
        }
        return $this->migrator->getMigrationFiles($paths);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['tenant', 'T', InputOption::VALUE_NONE, "Show the status of each migration for tenant database. '--database' option will be ignored. use '--domain' instead."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ]);
    }
}
