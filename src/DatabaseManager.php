<?php

namespace EJLab\Laravel\MultiTenant;

use Config;
use DB;
use App\Models\System\Tenant;
use EJLab\Laravel\MultiTenant\Exceptions\TenantDatabaseException;

class DatabaseManager 
{
    public $systemConnectionName;
    public $tenantConnectionName;
    public $tenantAdminConnectionName;

    protected $tenant;

    public function __construct(Tenant $tenant = NULL)
    {
        $this->systemConnectionName = Config('elmt.system-connection', 'system');
        $this->tenantConnectionName = Config('elmt.tenant-connection', 'tenant');
        $this->tenantAdminConnectionName = Config('elmt.tenant-admin-connection', 'tenant_admin');
        if (!is_null($tenant)) $this->setConnection($tenant);
    }

    public function setConnection(Tenant $tenant)
    {
        if (!is_null($tenant)) $this->tenant = $tenant;
        DB::purge($this->tenantAdminConnectionName);
        DB::purge($this->tenantConnectionName);
        Config::set('database.connections.'.$this->tenantAdminConnectionName, $this->getTenantAdminConfig());
        Config::set('database.connections.'.$this->tenantConnectionName, $this->getTenantConfig());
        DB::reconnect($this->tenantAdminConnectionName);
        DB::reconnect($this->tenantConnectionName);
    }

    protected function getTenantAdminConfig()
    {
        $config = Config::get('database.connections.'.$this->systemConnectionName);
        $config['host'] = $this->tenant->db_host;
        $config['port'] = $this->tenant->db_port;
        $config['database'] = '';

        return $config;
    }

    protected function getTenantConfig()
    {
        $config = $this->getTenantAdminConfig();
        $config['database'] = $this->getTenantDatabaseUsername();
        $config['username'] = $config['database'];
        $config['password'] = $this->getTenantDatabasePassword();

        if ($config['driver'] == 'sqlite') {
            $config['database'] = 'database/'.$config['database'].'.sqlite';
        }

        return $config;
    }

    protected function getTenantDatabaseUsername()
    {
        return str_replace(' ', '_', strtolower(Config::get('app.name', 'elmt'))).'_'.$this->tenant->domain;
    }

    protected function getTenantDatabasePassword()
    {
        return sha1($this->tenant->domain.$this->tenant->id.Config::get('elmt.key', 'elmt_secret_key'));
    }

    public function create()
    {
        $config = $this->getTenantConfig();

        if ($config['driver'] == 'sqlite') {
            touch($config['database']);
        } else {
            return DB::connection($this->tenantAdminConnectionName)->transaction(function () use ($config) {
                $result = false;
                $result = DB::connection($this->tenantAdminConnectionName)->statement(
                    "CREATE USER IF NOT EXISTS `{$config['username']}`@'%' IDENTIFIED BY '{$config['password']}';"
                );
                if (!$result) throw new TenantDatabaseException("Could not create user '{$config['username']}'");
    
                $result = DB::connection($this->tenantAdminConnectionName)->statement(
                    "CREATE DATABASE IF NOT EXISTS `{$config['database']}`;"
                );
                if (!$result) throw new TenantDatabaseException("Could not create database '{$config['database']}'");
    
                $result = DB::connection($this->tenantAdminConnectionName)->statement(
                    "GRANT ALL ON `{$config['database']}`.* TO `{$config['username']}`@'%';"
                );
                if (!$result) throw new TenantDatabaseException("Could not grant privileges to user '{$config['username']}' for '{$config['database']}'");
    
                return true;
            });
        }
    }

    public function delete()
    {
        $config = $this->getTenantConfig();

        if ($config['driver'] == 'sqlite') {
            unlink($config['database']);
        } else {
            return DB::connection($this->tenantAdminConnectionName)->transaction(function () use ($config) {
                $result = false;
                $result = DB::connection($this->tenantAdminConnectionName)->statement(
                    "REVOKE ALL ON `{$config['database']}`.* FROM `{$config['username']}`@'%';"
                );
                if (!$result) throw new TenantDatabaseException("Could not revoke privileges from user '{$config['username']}' for '{$config['database']}'");

                $result = DB::connection($this->tenantAdminConnectionName)->statement(
                    "DROP DATABASE IF EXISTS `{$config['database']}`;"
                );
                if (!$result) throw new TenantDatabaseException("Could not drop database '{$config['database']}'");

                $result = DB::connection($this->tenantAdminConnectionName)->statement(
                    "DROP USER IF EXISTS `{$config['username']}`@'%';"
                );
                if (!$result) throw new TenantDatabaseException("Could not drop user '{$config['username']}'");

                return true;
            });
        }
    }
}