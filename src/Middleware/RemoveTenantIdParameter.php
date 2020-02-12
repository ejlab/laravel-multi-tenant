<?php

namespace EJLab\Laravel\MultiTenant\Middleware;

use Closure;

class RemoveTenantIdParameter
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
        $request->route()->forgetParameter(config('elmt.tenant-id-parameter', 'domain'));
        return $next($request);
    }
}
