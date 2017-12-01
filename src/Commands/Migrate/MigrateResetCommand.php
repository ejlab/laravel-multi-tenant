<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use App\Models\System\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use DB;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Symfony\Component\Console\Input\InputOption;

class MigrateResetCommand extends ResetCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) return;

        $manager = new DatabaseManager();
        DB::setDefaultConnection($manager->systemConnectionName);

        $paths = $this->getMigrationPaths();

        if ($this->input->getOption('tenant')) {

            $domain = $this->input->getOption('domain') ?: 'all';
            if ($domain == 'all') $tenants = Tenant::all();
            else $tenants = Tenant::where('domain', $domain)->get();

            foreach ($paths as $path) $paths[] = $path.DIRECTORY_SEPARATOR.'tenant';

            $drawBar = (count($tenants) > 1);
            if ($drawBar) $bar = $this->output->createProgressBar(count($tenants));

            foreach ($tenants as $tenant) {
                
                $manager->setConnection($tenant);
                $this->migrator->setConnection($manager->tenantConnectionName);

                $this->info('');
                $this->info("Resetting migrations for '{$tenant->name}'...");

                if (! $this->migrator->repositoryExists()) {
                    if ($drawBar) $bar->advance();
                    $this->error(($drawBar?'  ':'')."No migrations found for '{$tenant->name}'.");
                    continue;
                }

                $this->migrator->reset($paths, $this->option('pretend'));

                // Once the migrator has run we will grab the note output and send it out to
                // the console screen, since the migrator itself functions without having
                // any instances of the OutputInterface contract passed into the class.
                foreach ($this->migrator->getNotes() as $note) {
                    $this->output->writeln($note);
                }

                if ($drawBar) $bar->advance();
                $this->info(($drawBar?'  ':'')."'{$tenant->name}' reseted.");
            }
            if ($drawBar) $bar->finish();
        } else {
            $this->migrator->setConnection($manager->systemConnectionName);

            // First, we'll make sure that the migration table actually exists before we
            // start trying to rollback and re-run all of the migrations. If it's not
            // present we'll just bail out with an info message for the developers.
            if (! $this->migrator->repositoryExists()) {
                return $this->comment('Migration table not found.');
            }

            foreach ($paths as $path) $paths[] = $path.DIRECTORY_SEPARATOR.'system';

            $this->migrator->reset($paths, $this->option('pretend'));

            // Once the migrator has run we will grab the note output and send it out to
            // the console screen, since the migrator itself functions without having
            // any instances of the OutputInterface contract passed into the class.
            foreach ($this->migrator->getNotes() as $note) {
                $this->output->writeln($note);
            }
        }
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
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['tenant', 'T', InputOption::VALUE_NONE, "Rollback all database migrations for tenant database."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ];
    }
}
