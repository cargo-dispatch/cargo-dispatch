<?php

// Add this method to your existing controller or create a new one
// app/Http/Controllers/ShipmentNotificationController.php

namespace App\Http\Controllers;

use App\Models\Customers\Customer;
use App\Models\Shipments\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShipmentNotificationController extends Controller
{
public function detail($id, $notificationId = null)
{
    $notification = null;

    if ($notificationId) {
        $notification = Auth::user()->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            // Ensure data is properly cast to array
            $notification->data = (array)$notification->data;
        }
    }

    $shipment = Shipment::with(['customer', 'driver'])->findOrFail($id);
    
    // dd($shipment);

    return view('shipment.notification-detail', [
        'shipment' => $shipment,
        'notification' => $notification,
        'displayStatus' => $notification ? 
                         ($notification->data['shipment_status'] ?? $shipment->status) : 
                         $shipment->status
    ]);
}
 public function index()
    {
        $notifications = auth::user()->notifications()->paginate(20); // or use ->get()
        return view('shipment.allnotifications', compact('notifications'));
    }

    // app/Http/Controllers/ShipmentNotificationController.php
 public function fetch()
{
    $user = Auth::user();
    if (!$user) {
        return response()->json(['total_count' => 0, 'displayed_count' => 0, 'notifications' => []]);
    }

    $user->unsetRelation('unreadNotifications');

    $totalUnreadCount = $user->unreadNotifications()->count();
    
    $notificationsToShow = $user
        ->unreadNotifications()
        ->latest()
        ->take(5)
        ->get();

    return response()->json([
        'total_count' => $totalUnreadCount, // The actual total count
        'displayed_count' => $notificationsToShow->count(), // Count of displayed notifications (max 5)
        'notifications' => $notificationsToShow->map(function($notification) {
            return [
                'id' => $notification->id,
                'shipment_id' => $notification->data['shipment_id'],
                'message' => $notification->data['message'] ?? 'New Shipment Created',
                'pickup' => $notification->data['pickup'] ?? '-',
                'drop' => $notification->data['drop'] ?? '-',
                'created_at' => $notification->created_at->format('M j, g:i a')
            ];
        })->toArray()
    ]);
}
 public function markAllAsRead(): JsonResponse
{
    $user = Auth::user();
    
    // Get count BEFORE deletion for accurate reporting
    $notificationCount = $user->unreadNotifications()->count();
    
    if ($notificationCount > 0) {
        // 1. Mark all as read
        $user->unreadNotifications->markAsRead();
        
        // 2. Delete all notifications (both read and unread)
        $user->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => $notificationCount . ' notifications have been cleared.',
            'cleared_count' => $notificationCount
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'No notifications to clear.',
        'cleared_count' => 0
    ]);
}
}
