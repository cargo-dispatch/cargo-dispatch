<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Facades\Log;

class NewShipmentCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $shipment;

    public function __construct($shipment)
    {
        $this->shipment = $shipment->load('customer');
        Log::info('NewShipmentCreated event created', ['shipment_id' => $this->shipment->id]);
    }

    public function broadcastWith()
    {
        return ['shipment' => $this->shipment];
        
    }

    public function broadcastOn()
    {
        return new PrivateChannel('admin.notifications');
    }

    public function broadcastAs()
    {
        return 'shipment.created';
    }
}
