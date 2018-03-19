<?php

namespace EJLab\Laravel\MultiTenant\Commands;

use App\Models\System\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use EJLab\Laravel\MultiTenant\Commands\Migrate\TenantCommand;
use Illuminate\Console\Command;

class TenantDestroyCommand extends Command
{
    use TenantCommand;

    protected $manager;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "tenant:destroy {--domain= : The domain for tenant. 'all' or null value for all tenants.}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop database and user for tenant.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->manager = new DatabaseManager();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tenants = $this->getTenants();

        $progressBar = $this->output->createProgressBar(count($tenants));

        foreach ($tenants as $tenant) {
            $this->info("Deleting database and user for '{$tenant->name}'...");

            $this->manager->setConnection($tenant);
            $this->manager->delete();

            $tenant->setup_has_done = FALSE;
            $tenant->save();

            $progressBar->advance();
            $this->info("  Database and user for '{$tenant->name}' deleted successfully.");
        }
    }
}
