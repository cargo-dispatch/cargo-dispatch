<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.notifications', function ($user) {
    return !empty($user);
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('shipments', function ($user) {
    return !empty($user);
});

Broadcast::channel('driver.{driverId}.shipments', function ($user, $driverId) {
    // Driver auth uses drivers table with sanctum guard; admins may also listen.
    if (isset($user->role_id)) {
        return true;
    }
    return (int) $user->id === (int) $driverId;
});

Broadcast::channel('customer.{customerId}.shipments', function ($user, $customerId) {
    if (isset($user->role_id)) {
        return true;
    }
    return (int) $user->id === (int) $customerId;
});