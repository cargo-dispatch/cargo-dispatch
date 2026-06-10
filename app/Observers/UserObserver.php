<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function created(User $user)
    {
        if ($user->role_id == 23) { // Admin role
            dispatch(function () use ($user) {
                try {
                    $user->syncConnectyCubeUser();
                } catch (\Exception $e) {
                    Log::error("Auto-sync failed for new admin: " . $e->getMessage());
                }
            });
        }
    }
}