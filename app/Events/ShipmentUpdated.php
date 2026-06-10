<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $customerId;
    public int $driverId;

    public function __construct(int $customerId, int $driverId)
    {
        $this->customerId = $customerId;
        $this->driverId   = $driverId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('customer.' . $this->customerId),
            new Channel('driver.' . $this->driverId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'shipment.updated';
    }
}
