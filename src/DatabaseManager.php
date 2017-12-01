<?php

namespace EJLab\Laravel\MultiTenant;

use Config;
use DB;
use App\Models\System\Tenant;
use App\Models\System\TenantInfo;
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
        $config = Config::get('database.connections.'.$this->tenantAdminConnectionName);
        $config['host'] = $this->tenant->host;

        return $config;
    }

    protected function getTenantConfig()
    {
        $config = $this->getTenantAdminConfig();
        $config['database'] = $this->getTenantDatabaseUsername();
        $config['username'] = $config['database'];
        $config['password'] = $this->getTenantDatabasePassword();

        return $config;
    }

    protected function getTenantDatabaseUsername()
    {
        return $this->tenant->domain;
    }

    protected function getTenantDatabasePassword()
    {
        $tenantInfo = TenantInfo::select(DB::raw("Right(master.dbo.fn_varbintohexstr(HashBytes('MD5',도메인 + convert(varchar, DB비밀번호갱신일자,20))),29) + '!' AS db_password"))
            ->where('domain', substr($this->tenant->domain, 2))
            ->get()
            ->first();
        return $tenantInfo->db_password;
    }

    public function create()
    {
        $config = $this->getTenantConfig();

        return DB::connection($this->tenantAdminConnectionName)->transaction(function () use ($config) {
            $result = false;
            $result = DB::connection($this->tenantAdminConnectionName)->statement(
                "CREATE USER IF NOT EXISTS `{$config['username']}`@'{$config['host']}' IDENTIFIED BY '{$config['password']}';"
            );
            if (!$result) throw new TenantDatabaseException("Could not create user '{$config['username']}'");

            $result = DB::connection($this->tenantAdminConnectionName)->statement(
                "CREATE DATABASE IF NOT EXISTS `{$config['database']}`;"
            );
            if (!$result) throw new TenantDatabaseException("Could not create database '{$config['database']}'");

            $result = DB::connection($this->tenantAdminConnectionName)->statement(
                "GRANT ALL ON `{$config['database']}`.* TO `{$config['username']}`@'{$config['host']}';"
            );
            if (!$result) throw new TenantDatabaseException("Could not grant privileges to user '{$config['username']}' for '{$config['database']}'");

            return true;
        });
    }

    public function delete()
    {
        $config = $this->getTenantConfig();

        return DB::connection($this->tenantAdminConnectionName)->transaction(function () use ($config) {
            $result = false;
            $result = DB::connection($this->tenantAdminConnectionName)->statement(
                "REVOKE ALL ON `{$config['database']}`.* FROM `{$config['username']}`@'{$config['host']}';"
            );
            if (!$result) throw new TenantDatabaseException("Could not revoke privileges from user '{$config['username']}' for '{$config['database']}'");

            $result = DB::connection($this->tenantAdminConnectionName)->statement(
                "DROP DATABASE IF EXISTS `{$config['database']}`;"
            );
            if (!$result) throw new TenantDatabaseException("Could not drop database '{$config['database']}'");

            $result = DB::connection($this->tenantAdminConnectionName)->statement(
                "DROP USER IF EXISTS `{$config['username']}`@'{$config['host']}';"
            );
            if (!$result) throw new TenantDatabaseException("Could not drop user '{$config['username']}'");

            return true;
        });
    }
}