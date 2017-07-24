<?php

namespace EJLab\Laravel\MultiTenant\Commands;

use App\Models\System\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use Illuminate\Console\Command;

class TenantSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "tenant:setup {--domain= : The domain for tenant. 'all' or null value for all tenants.}
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
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $domain = $this->input->getOption('domain') ?: 'all';
        if ($domain == 'all') $tenants = Tenant::where('setup_has_done', FALSE)->get();
        else $tenants = Tenant::where('domain', $domain)->get();

        $drawBar = (count($tenants) > 1);
        if ($drawBar) $bar = $this->output->createProgressBar(count($tenants));

        $manager = new DatabaseManager();

        foreach ($tenants as $tenant) {

            $this->info('');
            $this->info("Setting up database for '{$tenant->name}'...");

            $manager->setConnection($tenant);
            $manager->create();

            if ($this->input->getOption('migrate')) {
                $this->call('migrate', [
                    '--tenant' => TRUE,
                    '--domain' => $tenant->domain,
                    '--force' => TRUE
                ]);
            }

            $tenant->setup_has_done = TRUE;
            $tenant->save();

            if ($drawBar) $bar->advance();
            $this->info(($drawBar?'  ':'')."Database and user for '{$tenant->name}' created successfully.");
        }

        if ($drawBar) $bar->finish();
    }
}
