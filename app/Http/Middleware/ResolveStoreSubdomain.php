<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStoreSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) >= 3) {
            $subdomain = $parts[0];

            if ($subdomain !== 'www') {
                $business = Business::where('slug', $subdomain)->where('store_active', true)->first();

                if ($business) {
                    $request->merge(['store_business' => $business]);
                    $request->attributes->set('store_business', $business);

                    return $next($request);
                }
            }
        }

        return $next($request);
    }
}
