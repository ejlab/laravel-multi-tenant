<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use Illuminate\Database\Console\Migrations\MigrateCommand as BaseMigrateCommand;
use App\Tenant;

class MigrateCommand extends BaseMigrateCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "migrate {--database= : The database connection to use.}
                {--force : Force the operation to run when in production.}
                {--path= : The path of migrations files to be executed.}
                {--pretend : Dump the SQL queries that would be run.}
                {--seed : Indicates if the seed task should be re-run.}
                {--step : Force the migrations to be run so they can be rolled back individually.}
                {--T|tenant : Run migrations for tenant. '--database' option will be ignored. use '--domain' instead.}
                {--domain= : The domain for tenant. 'all' or null value for all tenants.}";
    
    // public function handle()
    // {
    //     $this->info('Migration start');
    //     $tenants = Tenant::all();
    //     foreach ($tenants as $tenant) {
    //         $this->call('migrate', ['--database'=>'tenant', '--path'=>'database/migrations/tenant']);
    //         $this->info("{$tenant->name} migrated.");
    //     }
    //     $this->info('Migration done');
    // }
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        if ($this->input->getOption('tenant')) {

            $domain = $this->input->getOption('domain') ?: 'all';
            
            $tenants = NULL;
            if ($domain == 'all') $tenants = Tenant::all();
            else $tenants = Tenant::where('domain', $domain)->get();

            $paths = [];
            foreach ($this->getMigrationPaths() as $path) {
                $paths[] = $path.DIRECTORY_SEPARATOR.'tenant';
            }

            foreach ($tenants as $tenant) {
                
                // todo: set connection

                $this->migrator->setConnection('tenant');

                if (! $this->migrator->repositoryExists()) {
                    $this->call('migrate:install', ['--tenant' => TRUE, '--domain' => $tenant->domain]);
                }

                // Next, we will check to see if a path option has been defined. If it has
                // we will use the path relative to the root of this installation folder
                // so that migrations may be run for any path within the applications.
                $this->migrator->run($paths, [
                    'pretend' => $this->option('pretend'),
                    'step' => $this->option('step'),
                ]);

                // Once the migrator has run we will grab the note output and send it out to
                // the console screen, since the migrator itself functions without having
                // any instances of the OutputInterface contract passed into the class.
                foreach ($this->migrator->getNotes() as $note) {
                    $this->output->writeln($note);
                }

                // Finally, if the "seed" option has been given, we will re-run the database
                // seed task to re-populate the database, which is convenient when adding
                // a migration and a seed at the same time, as it is only this command.
                if ($this->option('seed')) {
                    $this->call('db:seed', ['--force' => true]);
                }
            }


        } else parent::fire();

    }
}
