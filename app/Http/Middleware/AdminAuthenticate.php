<?php

namespace App\Http\Middleware;

use App\Models\Drivers\Driver;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        // ✅ Redirect to login if not authenticated
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        $currentUser = Auth::user();

        $drivers = Driver::select([
            'id', 'firstname', 'lastname', 'email', 'connectycube_id',
            'connectycube_login', 'connectycube_password', 'phoneno',
            DB::raw("'driver' as role_type")
        ])->whereNotNull('connectycube_id')->get();
        
        $admins = User::select([
            'id', 'first_name as firstname', 'last_name as lastname', 'email',
            'connectycube_id', 'connectycube_login', 'connectycube_password',
            DB::raw("'admin' as role_type")
        ])->where('role_id', 23)->whereNotNull('connectycube_id')->get();
        
        view()->share([
            'globalChatDrivers' => $drivers->merge($admins),
            'globalCurrentUser' => $currentUser,
            // REMOVED: globalChatCredentials from here
        ]);

        return $next($request);
    }
}