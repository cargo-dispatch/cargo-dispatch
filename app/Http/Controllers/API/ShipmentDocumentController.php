<?php

namespace App\Http\Controllers\API;

use App\Events\ShipmentStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Drivers\Driver;
use App\Models\ShipmentDocuments\ShipmentDocument;
use App\Models\Shipments\Shipment;
use App\Services\Integrations\Contracts\DocumentAiProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShipmentDocumentController extends Controller
{
    public function indexForShipment(Request $request, int $id): JsonResponse
    {
        $documents = ShipmentDocument::query()
            ->where('shipment_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'documents' => $documents,
        ]);
    }

    public function storeForDriver(Request $request, int $id, DocumentAiProviderInterface $documentAi): JsonResponse
    {
        $driver = Auth::user();
        if (!$driver instanceof Driver) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated driver',
            ], 401);
        }

        $validated = $request->validate([
            'document_type' => 'required|in:BOL,POD,RATE_CONFIRMATION,OTHER',
            'file' => 'required|file|max:10240', // <= 10MB
        ]);

        $shipment = Shipment::findOrFail($id);

        $storedPath = $request->file('file')->store('shipment-documents', 'public');

        $fullPath = storage_path('app/public/' . $storedPath);

        $document = ShipmentDocument::create([
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'document_type' => $validated['document_type'],
            'file_path' => $storedPath,
            'extraction_status' => 'pending',
        ]);

        try {
            $extracted = $documentAi->extractFields($fullPath);

            $confidence = $extracted['confidence'] ?? null;
            $fields = $extracted['fields'] ?? $extracted;

            $document->update([
                'extracted_fields' => $fields,
                'extraction_status' => 'extracted',
                'extraction_confidence' => $confidence,
                'extracted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Document AI extraction failed', [
                'shipment_id' => $shipment->id,
                'driver_id' => $driver->id,
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            $document->update([
                'extraction_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'document' => $document->fresh(),
        ]);
    }
}

