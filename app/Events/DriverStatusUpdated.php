<?php

namespace App\Events;

use App\Models\Drivers\Driver;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class DriverStatusUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(Driver $driver)
    {
        $this->payload = [
            'driver_id'           => (int) $driver->id,
            'current_duty_status' => (string) $driver->current_duty_status,
            'firstname'           => $driver->firstname,
            'lastname'            => $driver->lastname,
            'updated_at'          => now()->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver.status.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
