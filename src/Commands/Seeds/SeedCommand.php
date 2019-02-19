<?php

namespace EJLab\Laravel\MultiTenant\Commands\Seeds;

use App\Models\System\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use Illuminate\Console\Command;
use Illuminate\Database\Console\Seeds\SeedCommand as BaseCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

use DB;

class SeedCommand extends BaseCommand
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

        if ($this->input->getOption('tenant')) {

            $domain = $this->input->getOption('domain') ?: 'all';
            if ($domain == 'all') $tenants = Tenant::all();
            else $tenants = Tenant::where('domain', $domain)->get();

            $drawBar = (count($tenants) > 1);

            if ($drawBar) $bar = $this->output->createProgressBar(count($tenants));

            foreach ($tenants as $tenant) {

                $this->info('');
                $this->info("Seeding to '{$tenant->name}'...");

                $manager->setConnection($tenant);
                $this->resolver->setDefaultConnection($manager->tenantConnectionName);

                Model::unguarded(function () {
                    $this->getSeeder()->__invoke();
                });

                if ($drawBar) $bar->advance();
                $this->info(($drawBar?'  ':'')."Seed '{$tenant->name}' succeed.");
            }
            if ($drawBar) $bar->finish();

        } else {

            $this->resolver->setDefaultConnection($manager->systemConnectionName);

            Model::unguarded(function () {
                $this->getSeeder()->__invoke();
            });

        }
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->laravel->make($this->input->getOption('class'));

        return $class->setContainer($this->laravel)->setCommand($this);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['tenant', 'T', InputOption::VALUE_NONE, "Seed the database with records for tenant database."],
            ['domain', NULL, InputOption::VALUE_OPTIONAL, "The domain for tenant. 'all' or null value for all tenants."]
        ]);
    }
}
