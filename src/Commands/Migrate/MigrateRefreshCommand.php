<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use App\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Symfony\Component\Console\Input\InputOption;

class MigrateRefreshCommand extends RefreshCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->input->getOption('tenant')) {

            if (! $this->confirmToProceed()) {
                return;
            }

            $domain = $this->input->getOption('domain') ?: 'all';

            $manager = new DatabaseManager();
            DB::setDefaultConnection($manager->systemConnectionName);
            
            if ($domain == 'all') $tenants = Tenant::all();
            else $tenants = Tenant::where('domain', $domain)->get();

            $drawBar = (count($tenants) > 1);

            if ($drawBar) $bar = $this->output->createProgressBar(count($tenants));

            foreach ($tenants as $tenant) {

                $this->info('');
                $this->info("Refreshing migrations for '{$tenant->name}'...");
                
                $manager->setConnection($tenant);

                // Next we'll gather some of the options so that we can have the right options
                // to pass to the commands. This includes options such as which database to
                // use and the path to use for the migration. Then we'll run the command.
                $domain = $tenant->domain;

                $path = $this->input->getOption('path');

                $force = $this->input->getOption('force');

                // If the "step" option is specified it means we only want to rollback a small
                // number of migrations before migrating again. For example, the user might
                // only rollback and remigrate the latest four migrations instead of all.
                $step = $this->input->getOption('step') ?: 0;

                if ($step > 0) {
                    $this->runRollback($domain, $path, $step, $force);
                } else {
                    $this->runReset($domain, $path, $force);
                }

                // The refresh command is essentially just a brief aggregate of a few other of
                // the migration commands and just provides a convenient wrapper to execute
                // them in succession. We'll also see if we need to re-seed the database.
                $this->call('migrate', [
                    '--tenant' => TRUE,
                    '--domain' => $domain,
                    '--path' => $path,
                    '--force' => $force,
                ]);

                if ($this->needsSeeding()) {
                    $this->runSeeder($domain);
                }

                if ($drawBar) $bar->advance();
                $this->info(($drawBar?'  ':'')."Migrations for '{$tenant->name}' refreshed.");
            }

            if ($drawBar) $bar->finish();
        } else parent::fire();

    }

    /**
     * Run the rollback command.
     *
     * @param  string  $domain
     * @param  string  $path
     * @param  bool  $step
     * @param  bool  $force
     * @return void
     */
    protected function runRollback($domain, $path, $step, $force)
    {
        $this->call('migrate:rollback', [
            '--tenant' => TRUE,
            '--domain' => $domain,
            '--path' => $path,
            '--step' => $step,
            '--force' => $force,
        ]);
    }

    /**
     * Run the reset command.
     *
     * @param  string  $domain
     * @param  string  $path
     * @param  bool  $force
     * @return void
     */
    protected function runReset($domain, $path, $force)
    {
        $this->call('migrate:reset', [
            '--tenant' => TRUE,
            '--domain' => $domain,
            '--path' => $path,
            '--force' => $force,
        ]);
    }

    /**
     * Run the database seeder command.
     *
     * @param  string  $domain
     * @return void
     */
    protected function runSeeder($domain)
    {
        $this->call('db:seed', [
            '--tenant' => TRUE,
            '--domain' => $domain,
            '--class' => $this->option('seeder') ?: 'DatabaseSeeder',
            '--force' => $this->option('force'),
        ]);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['tenant', 'T', InputOption::VALUE_NONE, "Reset and re-run all migrations for tenant database. '--database' option will be ignored. use '--domain' instead."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ]);
    }
}
