<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            if (! Session::has('active_business_id') && $user->business_id) {
                Session::put('active_business_id', $user->business_id);
            }
        }

        return $next($request);
    }
}
