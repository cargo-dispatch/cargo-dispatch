<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Drivers\Driver;

/**
 * Service to send push notifications via Expo
 * Docs: https://docs.expo.dev/push-notifications/push-notification-setup/
 */
class ExpoNotificationService
{
    private const EXPO_PUSH_API = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification to a driver
     * 
     * @param Driver $driver
     * @param string $title
     * @param string $body
     * @param array $data Additional data to include
     * @return bool
     */
    public static function sendToDriver(Driver $driver, string $title, string $body, array $data = []): bool
    {
        if (!$driver->expo_push_token) {
            Log::warning('Driver has no expo push token', ['driver_id' => $driver->id]);
            return false;
        }

        return self::send($driver->expo_push_token, $title, $body, $data);
    }

    /**
     * Send push notification to multiple drivers
     * 
     * @param array<Driver> $drivers
     * @param string $title
     * @param string $body
     * @param array $data
     * @return int Number of successful sends
     */
    public static function sendToDrivers(array $drivers, string $title, string $body, array $data = []): int
    {
        $successful = 0;
        foreach ($drivers as $driver) {
            if (self::sendToDriver($driver, $title, $body, $data)) {
                $successful++;
            }
        }
        return $successful;
    }

    /**
     * Send push notification via Expo API
     * 
     * @param string $expoPushToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    private static function send(string $expoPushToken, string $title, string $body, array $data = []): bool
    {
        try {
            $payload = [
                'to' => $expoPushToken,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => empty($data) ? new \stdClass() : $data,
                'badge' => 1,
                'priority' => 'high',
            ];

            $response = Http::timeout(10)->post(self::EXPO_PUSH_API, $payload);

            if ($response->successful()) {
                Log::debug('Push notification sent successfully', [
                    'token' => substr($expoPushToken, 0, 10) . '...',
                    'title' => $title,
                ]);
                return true;
            }

            Log::error('Failed to send push notification', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Error sending push notification', [
                'error' => $e->getMessage(),
                'title' => $title,
            ]);
            return false;
        }
    }

    /**
     * Notify driver of shipment assignment
     */
    public static function notifyShipmentAssigned(Driver $driver, $shipment): bool
    {
        return self::sendToDriver(
            $driver,
            '📦 New Shipment Assigned',
            "Pickup at {$shipment->pickup_address}",
            [
                'type' => 'shipment_assigned',
                'shipment_id' => $shipment->id,
                'weight' => $shipment->weight,
                'distance' => $shipment->distance_miles,
            ]
        );
    }

    /**
     * Notify driver that shipment status was updated
     */
    public static function notifyShipmentUpdated(Driver $driver, $shipment, string $newStatus): bool
    {
        $statusLabels = [
            'assigned' => '✅ Assigned',
            'picked_up' => '📍 Picked Up',
            'in_transit' => '🚚 In Transit',
            'delivered' => '✅ Delivered',
        ];

        $statusLabel = $statusLabels[$newStatus] ?? $newStatus;

        return self::sendToDriver(
            $driver,
            'Shipment Updated',
            "Shipment #{$shipment->id} status: {$statusLabel}",
            [
                'type' => 'shipment_updated',
                'shipment_id' => $shipment->id,
                'status' => $newStatus,
            ]
        );
    }
}
