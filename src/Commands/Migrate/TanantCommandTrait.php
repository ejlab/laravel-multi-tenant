<?php

namespace EJLab\Laravel\MultiTenant\Commands\Migrate;

use App\Models\System\Tenant;
use Illuminate\Support\Facades\Schema;

use DB;

/**
 * 
 */
trait TenantCommand
{
    protected $manager;

    protected function getTenants($setup = TRUE) {
        DB::setDefaultConnection($this->manager->systemConnectionName);
        
        if (Schema::hasTable(Tenant::getTableName())) {
            $qb = Tenant::where('setup_has_done', $setup);
            if ($this->option('domain')) $qb->where('domain', $this->option('domain'));
            $tenants = $qb->get();

            if (count($tenants) == 0) {
                $this->error('No available tenants found');
                exit;
            }
            return $tenants;
        } else {
            $this->error('tenants table is not exists in system database');
            exit;
        }
    }

    protected function setSystemDatabase() {
        $this->input->setOption('database', $this->manager->systemConnectionName);
    }

    protected function setTenantDatabase() {
        $this->input->setOption('database', $this->manager->tenantConnectionName);
    }

    /**
     * Get all of the migration paths.
     *
     * @return array
     */
    protected function getMigrationPaths()
    {
        $type = $this->option('tenant') ? 'tenant' : 'system';
        $paths = parent::getMigrationPaths();
        foreach ($paths as $path) $paths[] = $path.DIRECTORY_SEPARATOR.$type;

        return $paths;
    }
}
