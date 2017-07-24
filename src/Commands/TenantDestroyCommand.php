<?php

namespace EJLab\Laravel\MultiTenant\Commands;

use App\Models\System\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use Illuminate\Console\Command;

class TenantDestroyCommand extends Command
{
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
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $domain = $this->input->getOption('domain') ?: 'all';
        if ($domain == 'all') $tenants = Tenant::onlyTrashed()->where('setup_has_done', TRUE)->get();
        else $tenants = Tenant::onlyTrashed()->where('domain', $domain)->get();

        $drawBar = (count($tenants) > 1);
        if ($drawBar) $bar = $this->output->createProgressBar(count($tenants));

        $manager = new DatabaseManager();

        foreach ($tenants as $tenant) {

            $this->info('');
            $this->info("Deleting database and user for '{$tenant->name}'...");

            $manager->setConnection($tenant);
            $manager->delete();

            $tenant->setup_has_done = FALSE;
            $tenant->save();

            if ($drawBar) $bar->advance();
            $this->info(($drawBar?'  ':'')."Database and user for '{$tenant->name}' deleted successfully.");
        }

        if ($drawBar) $bar->finish();
    }
}
