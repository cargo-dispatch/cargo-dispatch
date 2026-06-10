<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class ShipmentChanged implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public string $action;
    public int $shipmentId;

    public function __construct(string $action, int $shipmentId)
    {
        $this->action     = $action;      // 'created' | 'deleted' | 'updated'
        $this->shipmentId = $shipmentId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('shipments');
    }

    public function broadcastAs(): string
    {
        return 'shipment.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'action'      => $this->action,
            'shipment_id' => $this->shipmentId,
        ];
    }
}
