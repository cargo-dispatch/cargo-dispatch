<?php

namespace App\Http\Middleware\Permissions;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleUnauthorizedAttempts
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
  
     public function handle(Request $request, Closure $next): Response
     {
        
         $response = $next($request);
 
         // Check if the response is a 403 (Forbidden)
         if ($response->getStatusCode() === 403) {
             if ($request->expectsJson()) {
                 return response()->json([
                     'message' => 'You don\'t have access to perform this action.',
                 ], 403);
             }
 
             return redirect()->back()->with('error', 'You don\'t have access to perform this action.');
         }
 
         return $response;
     }
}
