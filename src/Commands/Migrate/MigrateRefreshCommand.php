<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use App\Models\System\Tenant;
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
        if (! $this->confirmToProceed()) return;

        $manager = new DatabaseManager();
        DB::setDefaultConnection($manager->systemConnectionName);

        if ($this->input->getOption('tenant')) {
            
            $domain = $this->input->getOption('domain') ?: 'all';
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
                $force = $this->input->getOption('force');

                // If the "step" option is specified it means we only want to rollback a small
                // number of migrations before migrating again. For example, the user might
                // only rollback and remigrate the latest four migrations instead of all.
                $step = $this->input->getOption('step') ?: 0;

                if ($step > 0) {
                    $this->runRollback($domain, NULL, $step, $force);
                } else {
                    $this->runReset($domain, NULL, $force);
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
                    $this->runSeeder($domain);
                }

                if ($drawBar) $bar->advance();
                $this->info(($drawBar?'  ':'')."Migrations for '{$tenant->name}' refreshed.");
            }

            if ($drawBar) $bar->finish();
        } else {
            // Next we'll gather some of the options so that we can have the right options
            // to pass to the commands. This includes options such as which database to
            // use and the path to use for the migration. Then we'll run the command.
            $database = $manager->systemConnectionName;
            $force = $this->input->getOption('force');

            // If the "step" option is specified it means we only want to rollback a small
            // number of migrations before migrating again. For example, the user might
            // only rollback and remigrate the latest four migrations instead of all.
            $step = $this->input->getOption('step') ?: 0;

            if ($step > 0) {
                $this->runRollback($database, NULL, $step, $force);
            } else {
                $this->runReset($database, NULL, $force);
            }

            // The refresh command is essentially just a brief aggregate of a few other of
            // the migration commands and just provides a convenient wrapper to execute
            // them in succession. We'll also see if we need to re-seed the database.
            $this->call('migrate', ['--force' => $force]);

            if ($this->needsSeeding()) {
                $this->runSeeder($database);
            }
        }
    }

    /**
     * Run the rollback command.
     *
     * @param  string  $domain
     * @param  bool  $step
     * @param  bool  $force
     * @return void
     */
    protected function runRollback($domain, $path, $step, $force)
    {
        $options = [
            '--step' => $step,
            '--force' => $force,
        ];
        if ($this->input->getOption('tenant')) {
            $options['--tenant'] = TRUE;
            $options['--domain'] = $domain;
        }
        $this->call('migrate:rollback', $options);
    }

    /**
     * Run the reset command.
     *
     * @param  string  $domain
     * @param  bool  $force
     * @return void
     */
    protected function runReset($domain, $path, $force)
    {
        $options = ['--force' => $force];
        if ($this->input->getOption('tenant')) {
            $options['--tenant'] = TRUE;
            $options['--domain'] = $domain;
        }
        $this->call('migrate:reset', $options);
    }

    /**
     * Run the database seeder command.
     *
     * @param  string  $domain
     * @return void
     */
    protected function runSeeder($domain)
    {
        $options = [
            '--class' => $this->option('seeder') ?: 'DatabaseSeeder',
            '--force' => $this->option('force'),
        ];
        if ($this->input->getOption('tenant')) {
            $options['--tenant'] = TRUE;
            $options['--domain'] = $domain;
        }
        $this->call('db:seed', $options);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
            ['seeder', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder.'],
            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted & re-run.'],
            ['tenant', 'T', InputOption::VALUE_NONE, "Reset and re-run all migrations for tenant database."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ];
    }
}
