<?php

namespace EJLab\Laravel\MultiTenant\Middleware;

use Closure;

class SaveDomainParameterInRequest
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
        $domain = $request->route()->parameters()['domain'];
        $request->session()->put('domain', $domain);
        return $next($request);
    }
}
