<?php

namespace App\Http\Middleware;

use App\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        $tenant = Tenant::where('domain', $host)->first();

        if ($tenant) {
            if (!$tenant->is_active) {
                abort(403, 'Tenant is inactive.');
            }

            // Configure the connection
            $tenant->configure();

            // Bind tenant to container for easy access
            app()->instance('tenant', $tenant);
        }

        return $next($request);
    }
}
