<?php

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;

class NewShipmentNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $shipment;

    public function __construct($shipment)
    {
        $this->shipment = $shipment;
        Log::info('NewShipmentNotification created', ['shipment_id' => $shipment->id]);
    }

    public function via($notifiable)
    {
        Log::info('Notification via called', ['notifiable_id' => $notifiable->id]);
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => get_class($this),
            'data' => $this->toDatabase($notifiable),
            'read_at' => null,
            'created_at' => now()->toDateTimeString(),
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
        ]);
    }
public $isStatusUpdate = false; // Add this property

public function toDatabase($notifiable)
{
    $customerName = $this->shipment->customer 
        ? $this->shipment->customer->first_name . ' ' . $this->shipment->customer->last_name
        : 'Unknown Customer';

    return [
        'message' => $this->isStatusUpdate
            ? "Shipment status updated to: " . ucfirst($this->shipment->status)
            : "New shipment created by {$customerName}",
        'pickup' => $this->shipment->pickup_address,
        'drop' => $this->shipment->drop_address,
        'shipment_id' => $this->shipment->id,
        'shipment_status' => $this->shipment->status, // Ensure status is included
        'customer_name' => $customerName,
        'updated_at' => now()->toDateTimeString(),
        'is_status_update' => $this->isStatusUpdate
    ];
}
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('admin.notifications');
    }

    public function broadcastAs()
    {
        return 'shipment.created'; // matches frontend listener
    }
}
