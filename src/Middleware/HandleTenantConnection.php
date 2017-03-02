<?php

namespace EJLab\Laravel\MultiTenant\Middleware;

use Closure;
use App\Tenant;
use EJLab\Laravel\MultiTenant\DatabaseManager;

class HandleTenantConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $parameters = $request->route()->parameters();
        if (isset($parameters['domain'])) {
            $tenant = Tenant::where('domain', $parameters['domain'])->get()->first();
            if ($tenant) {
                $manager = new DatabaseManager();
                $manager->setConnection($tenant);

                return $next($request);
            }
        }

        return abort(404);
    }
}
