<?php

namespace EJLab\Laravel\MultiTenant\Commands;

use App\Models\System\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use EJLab\Laravel\MultiTenant\Commands\Migrate\TenantCommandTrait;
use Illuminate\Console\Command;

class TenantSetupCommand extends Command
{
    use TenantCommandTrait;

    protected $manager;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "tenant:setup
                            {--domain= : The domain for tenant. 'all' or null value for all tenants.}
                            {--m|migrate : Run migration after setup.}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database and user for tenant.';

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
        $tenants = $this->getTenants(FALSE);
        $progressBar = $this->output->createProgressBar(count($tenants));
        foreach ($tenants as $tenant) {
            $this->info("Setting up database for '{$tenant->name}'...");

            $this->manager->setConnection($tenant);
            $this->manager->create();

            if ($this->option('migrate')) {
                $this->call('migrate', [
                    '--tenant' => TRUE,
                    '--domain' => $tenant->domain,
                    '--force' => TRUE
                ]);
            }

            $tenant->setup_has_done = TRUE;
            $tenant->save();

            $progressBar->advance();
            $this->info("  Database and user for '{$tenant->name}' created successfully.");
        }
    }
}
