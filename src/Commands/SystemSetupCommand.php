<?php

namespace EJLab\Laravel\MultiTenant\Commands;

use App\Models\System\Tenant;
use App\Models\Master\Company;
use EJLab\Laravel\MultiTenant\DatabaseManager;
use EJLab\Laravel\MultiTenant\Commands\Migrate\TenantCommandTrait;
use Illuminate\Console\Command;
use DB;

class SystemSetupCommand extends Command
{
    use TenantCommandTrait;

    protected $manager;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "system:setup                            
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
        //$company = new Company;
        //dd("가즈아");

        //migrate 테이블 생성
        try {
            \Artisan::call("migrate:install --database=nuria_pos");
        } catch (\Exception $e) {
            echo " 1.is already installed";
        }

        //system 테이블 생성
        try {
            \Artisan::call("migrate --database=nuria_pos --path=database/migrations/system");            
        } catch (\Exception $e) {
            echo " 2.is already migrate";
        }

        $company_count = DB::connection('master')->table('company')        
        ->whereRaw("domain ='kwon'")
        ->count();
        
        $company = new Company;
        $company->domain = "kwon";
        $company->testing = 0;
        $company->sellmate_domain = "kwon";
        $company->password = \DB::raw('sha1(1234)');        
        $company->db_host = \DB::raw("INET_ATON('127.0.0.1')");
        if($company_count == 0){           
            $company->save();
        }        
        //company 정보 저장.
        if($company_count == 0){
        DB::connection('master')->statement("
                UPDATE company SET expiration_date=DATE_ADD(join_date, INTERVAL 14 DAY) WHERE domain='kwon';
            ");
        }

        try {           
            if($company_count == 0){
            
                \Artisan::call("tenant:setup --domain=kwon");
            }  
        } catch (\Exception  $e) {
            
            echo " 3.tenant is already setup";
        }
        
        try {
            \Artisan::call("migrate:install -T --domain=kwon");
            DB::connection('tenant_admin')->unprepared("ALTER USER 'kwon'@'%' IDENTIFIED WITH mysql_native_password BY '{$company->getDatabasePassword()}';");
        } catch (\Exception $e) {
            echo " 4.tenant is already installed";
        }

        try {
             \Artisan::call("migrate -T --path=database/migrations/tenant --domain=kwon --force");
        } catch (\Exception $e) {
            echo " 5.tenant is already migrate";
        }

        //seeding check        
        $count = DB::connection('company')->table('product')                
        ->count();     

        echo " 6.start seed";

        if($count==0){
            //\Artisan::call("db:seed --force");
            try {
                \Artisan::call("db:seed --force");
            } catch (\Exception $e) {
                echo " 6.tenant is already seed";
            }
        }
        echo " finish";        
    }
}
