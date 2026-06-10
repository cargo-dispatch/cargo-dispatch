<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBroadcast
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('admin')) { // Using your admin guard
            return $next($request);
        }

        abort(403, 'Unauthorized');
    }
}