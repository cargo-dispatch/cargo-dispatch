<?php

namespace App\Http\Controllers\API;

use App\Events\ShipmentRealtimeUpdated;
use App\Http\Controllers\Controller;
use App\Models\Drivers\Driver;
use App\Models\ShipmentDocuments\ShipmentDocument;
use App\Models\Shipments\Shipment;
use App\Models\VehicleAssignment\VehicleAssignment;
use App\Services\Integrations\Contracts\WeatherProviderInterface;
use App\Services\Notifications\ExpoNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShipmentWorkflowController extends Controller
{
    /**
     * Get shipment with weather and documents info
     */
    public function getShipmentDetail(Request $request, int $id, WeatherProviderInterface $weather): JsonResponse
    {
        $driver = Auth::user();
        if (!$driver instanceof Driver) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $shipment = Shipment::with('documents')->findOrFail($id);

        if (!$this->driverOwnsShipment($driver->id, $shipment)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $pickupWeather = $weather->getWeatherForLocation($shipment->pickup_address);
            $dropWeather = $weather->getWeatherForLocation($shipment->drop_address);
        } catch (\Throwable $e) {
            Log::warning('Weather fetch failed', ['error' => $e->getMessage()]);
            $pickupWeather = null;
            $dropWeather = null;
        }

        $documents = $shipment->documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'type' => $doc->document_type,
                'status' => $doc->extraction_status,
                'file_path' => $doc->file_path,
                'extracted_fields' => $doc->extracted_fields,
                'confidence' => $doc->extraction_confidence,
                'uploaded_at' => $doc->created_at,
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'shipment' => [
                'id' => $shipment->id,
                'status' => $shipment->status,
                'pickup_address' => $shipment->pickup_address,
                'drop_address' => $shipment->drop_address,
                'pickup_time' => $shipment->pickup_time,
                'delivery_time' => $shipment->delivery_time,
                'weight' => $shipment->weight,
                'pallets' => $shipment->pallets,
                'special_instructions' => $shipment->special_instructions,
                'distance_miles' => $shipment->distance_miles,
                'distance_text' => $shipment->distance_text,
            ],
            'weather' => [
                'pickup' => $pickupWeather,
                'drop' => $dropWeather,
            ],
            'documents' => $documents,
            'workflow_state' => $this->getWorkflowState($shipment->status),
        ]);
    }

    /**
     * Execute workflow action: start_trip, complete_delivery
     */
    public function executeWorkflowAction(Request $request, int $id): JsonResponse
    {
        $driver = Auth::user();
        if (!$driver instanceof Driver) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        Log::info('workflow_action_hit', [
            'shipment_id' => $id,
            'driver_id' => $driver->id,
            'has_file' => $request->hasFile('file'),
            'action' => $request->input('action'),
            'all_inputs' => $request->except('file'),
        ]);

        $validated = $request->validate([
            'action' => 'required|in:start_trip,complete_delivery',
            'document_type' => 'nullable|in:BOL,POD,RATE_CONFIRMATION',
            'file' => 'nullable|file|max:10240',
            'notes' => 'nullable|string|max:500',
        ]);

        $shipment = Shipment::findOrFail($id);

        if (!$this->driverOwnsShipment($driver->id, $shipment)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $action = $validated['action'];

        Log::info('workflow-action hit', [
            'shipment_id' => $shipment->id,
            'status' => $shipment->status,
            'action' => $action,
            'driver_id' => $driver->id,
            'has_file' => $request->hasFile('file'),
        ]);

        $validTransitions = [
            'assigned'   => ['start_trip'],
            'picked_up'  => ['start_trip'],
            'in_transit' => ['complete_delivery'],
        ];

        if (!isset($validTransitions[$shipment->status]) || !in_array($action, $validTransitions[$shipment->status])) {
            return response()->json([
                'success' => false,
                'message' => "Action '{$action}' not allowed for status '{$shipment->status}'",
            ], 422);
        }

        $document = null;

        // Handle document upload if provided - NO OCR EXTRACTION
        if ($request->hasFile('file')) {
            $storedPath = $request->file('file')->store('shipment-documents', 'public');
            
            $documentType = $validated['document_type'] ?? ($action === 'start_trip' ? 'BOL' : 'POD');
            
            $document = ShipmentDocument::create([
                'shipment_id' => $shipment->id,
                'driver_id' => $driver->id,
                'document_type' => $documentType,
                'file_path' => $storedPath,
                'extraction_status' => 'uploaded', // Just mark as uploaded, no extraction
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        // Execute the workflow action (update shipment status)
        $statusMap = [
            'start_trip' => 'in_transit',
            'complete_delivery' => 'delivered',
        ];

        $newStatus = $statusMap[$action];
        $shipment->update(['status' => $newStatus]);
        $shipment->refresh();
        event(new ShipmentRealtimeUpdated('status_updated', $shipment));
        ExpoNotificationService::notifyShipmentUpdated($driver, $shipment, $newStatus);

        return response()->json([
            'success' => true,
            'message' => "Shipment {$action} successful",
            'shipment' => [
                'id' => $shipment->id,
                'status' => $shipment->status,
            ],
            'document' => $document ? [
                'id' => $document->id,
                'type' => $document->document_type,
                'status' => $document->extraction_status,
                'file_path' => $document->file_path,
            ] : null,
        ]);
    }

    /**
     * Get weather for a specific address
     */
    public function getWeatherForAddress(Request $request, WeatherProviderInterface $weather): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
        ]);

        try {
            $weatherData = $weather->getWeatherForLocation($validated['address']);

            return response()->json([
                'success' => true,
                'weather' => $weatherData,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Weather fetch failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Weather data unavailable',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // True if driver is directly assigned OR is linked to the shipment's vehicle via VehicleAssignment
    private function driverOwnsShipment(int $driverId, Shipment $shipment): bool
    {
        if ($shipment->driver_id === $driverId) return true;
        if (!$shipment->vehicle_id) return false;
        return VehicleAssignment::where('vehicle_id', $shipment->vehicle_id)
            ->where('driver_id', $driverId)
            ->exists();
    }

    /**
     * Determine workflow state based on shipment status
     */
    private function getWorkflowState(string $status): array
    {
        $states = [
            'assigned' => [
                'current_step' => 'start_trip',
                'next_action' => 'start_trip',
                'require_document' => true,
                'document_type' => 'BOL',
                'label' => 'Start Trip',
                'description' => 'Upload Bill of Lading (BOL) to begin your trip',
            ],
            'picked_up' => [
                'current_step' => 'start_trip',
                'next_action' => 'start_trip',
                'require_document' => true,
                'document_type' => 'BOL',
                'label' => 'Start Trip',
                'description' => 'Upload Bill of Lading (BOL) to begin your trip',
            ],
            'in_transit' => [
                'current_step' => 'complete',
                'next_action' => 'complete_delivery',
                'require_document' => true,
                'document_type' => 'POD',
                'label' => 'Complete Delivery',
                'description' => 'Upload Proof of Delivery (POD) to complete shipment',
            ],
            'delivered' => [
                'current_step' => 'completed',
                'next_action' => null,
                'require_document' => false,
                'label' => 'Delivered',
                'description' => 'Shipment delivered successfully',
            ],
        ];

        return $states[$status] ?? [];
    }
}