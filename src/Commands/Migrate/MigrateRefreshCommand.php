<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use App\Models\System\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Symfony\Component\Console\Input\InputOption;

class MigrateRefreshCommand extends RefreshCommand
{
    use TenantCommand;

    public function __construct()
    {
        parent::__construct();

        $this->manager = new DatabaseManager();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) return;

        if ($this->input->getOption('tenant')) {
            $tenants = $this->getTenants();
            $progressBar = $this->output->createProgressBar(count($tenants));
            $this->setTenantDatabase();
            foreach ($tenants as $tenant) {
                $this->manager->setConnection($tenant);
                $this->info("Refreshing migrations for '{$tenant->name}'...");
                
                $domain = $tenant->domain;

                // Next we'll gather some of the options so that we can have the right options
                // to pass to the commands. This includes options such as which database to
                // use and the path to use for the migration. Then we'll run the command.
                $force = $this->input->getOption('force');

                // If the "step" option is specified it means we only want to rollback a small
                // number of migrations before migrating again. For example, the user might
                // only rollback and remigrate the latest four migrations instead of all.
                $step = $this->input->getOption('step') ?: 0;

                if ($step > 0) {
                    $this->call('migrate:rollback', [
                        '--tenant' => TRUE,
                        '--domain' => $domain,
                        '--step' => $step,
                        '--force' => $force,
                    ]);
                } else {
                    $this->call('migrate:reset', [
                        '--tenant' => TRUE,
                        '--domain' => $domain,
                        '--force' => $force,
                    ]);
                }

                // The refresh command is essentially just a brief aggregate of a few other of
                // the migration commands and just provides a convenient wrapper to execute
                // them in succession. We'll also see if we need to re-seed the database.
                $this->call('migrate', [
                    '--tenant' => TRUE,
                    '--domain' => $domain,
                    '--force' => $force,
                ]);

                if ($this->needsSeeding()) {
                    $this->runSeeder($this->manager->tenantConnectionName);
                }

                $progressBar->advance();
                $this->info("  Migrations for '{$tenant->name}' refreshed.");
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
            ['tenant', 'T', InputOption::VALUE_NONE, "Reset and re-run all migrations for tenant database."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ], parent::getOptions());
    }
}
