<?php

namespace App\Events;

use App\Models\Shipments\Shipment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ShipmentRealtimeUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public string $eventType;
    public array $shipment;
    private ?int $driverIdOverride;

    public function __construct(string $eventType, Shipment $shipment, ?int $driverIdOverride = null)
    {
        $this->driverIdOverride = $driverIdOverride;
        $this->eventType = $eventType;
        $this->shipment = [
            'id'             => (int) $shipment->id,
            'status'         => (string) $shipment->status,
            'driver_id'      => $shipment->driver_id ? (int) $shipment->driver_id : null,
            'vehicle_id'     => $shipment->vehicle_id ? (int) $shipment->vehicle_id : null,
            'customer_id'    => $shipment->customer_id ? (int) $shipment->customer_id : null,
            'pickup_address' => $shipment->pickup_address,
            'drop_address'   => $shipment->drop_address,
            'pickup_time'    => optional($shipment->pickup_time)->toIso8601String(),
            'delivery_time'  => optional($shipment->delivery_time)->toIso8601String(),
            'weight'         => $shipment->weight,
            'pallets'        => $shipment->pallets,
            'distance_miles' => $shipment->distance_miles,
            'updated_at'        => optional($shipment->updated_at)->toIso8601String(),
            'current_latitude'  => $shipment->driver?->current_latitude,
            'current_longitude' => $shipment->driver?->current_longitude,
        ];

        Log::info('shipment_realtime_event_emitted', [
            'event_type' => $this->eventType,
            'shipment_id' => $this->shipment['id'],
            'driver_id' => $this->shipment['driver_id'],
            'vehicle_id' => $this->shipment['vehicle_id'],
        ]);
    }

    public function broadcastOn(): array
    {
        $effectiveDriverId = $this->shipment['driver_id'] ?? $this->driverIdOverride;

        $channels = [new PrivateChannel('admin.notifications')];

        // Skip private-shipments when we have a specific driver target — the driver channel
        // already delivers the event, and broadcasting to both causes double notifications on mobile.
        if (!$effectiveDriverId) {
            $channels[] = new PrivateChannel('shipments');
        }

        if (!empty($effectiveDriverId)) {
            $channels[] = new PrivateChannel('driver.' . $effectiveDriverId . '.shipments');
        }

        $customerId = $this->shipment['customer_id'] ?? null;
        if (!empty($customerId)) {
            $channels[] = new PrivateChannel('customer.' . $customerId . '.shipments');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'shipment.realtime.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event_type' => $this->eventType,
            'shipment_id' => $this->shipment['id'],
            'shipment' => $this->shipment,
        ];
    }
}
