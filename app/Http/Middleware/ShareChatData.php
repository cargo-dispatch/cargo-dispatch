<?php
// app/Http/Middleware/ShareChatData.php

namespace App\Http\Middleware;

use App\Models\Drivers\Driver;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShareChatData
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $currentUser = Auth::user();
            
            // Get drivers
            $drivers = Driver::select([
                'id', 'firstname', 'lastname', 'email', 'connectycube_id', 
                'connectycube_login', 'connectycube_password', 'phoneno',
                DB::raw("'driver' as role_type")
            ])->whereNotNull('connectycube_id')->get();

            // Get admins
            $admins = \App\Models\User::select([
                'id', 'first_name as firstname', 'last_name as lastname', 'email',
                'connectycube_id', 'connectycube_login', 'connectycube_password',
                DB::raw("'admin' as role_type")
            ])->where('role_id', 23)->whereNotNull('connectycube_id')->get();

            // Merge and share
            view()->share([
                'globalChatDrivers' => $drivers->merge($admins),
                'globalCurrentUser' => $currentUser,
                'globalChatCredentials' => [
                    'appId' => config('services.connectycube.app_id'),
                    'authKey' => config('services.connectycube.auth_key'),
                    'authSecret' => config('services.connectycube.auth_secret'),
                ]
            ]);
        }

        return $next($request);
    }
}