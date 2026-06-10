<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShipmentDocuments\ShipmentDocument;
use App\Models\Shipments\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminShipmentDocumentController extends Controller
{
    /**
     * Get shipment documents with filters
     */
    public function getDocuments(Request $request): JsonResponse
    {
        $filter = $request->input('filter', '');
        $status = $request->input('status', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);

        // Start query
        $query = ShipmentDocument::with(['shipment', 'driver'])
            ->orderByDesc('created_at');

        // Filter by document type
        if ($filter) {
            $query->where('document_type', $filter);
        }

        // Filter by extraction status
        if ($status) {
            $query->where('extraction_status', $status);
        }

        // Filter by pending (extraction_status = pending)
        if ($request->input('filter') === 'pending') {
            $query->where('extraction_status', 'pending');
        }

        $documents = $query->paginate($perPage, ['*'], 'page', $page);

        // Format response
        $formattedData = $documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'shipment_id' => $doc->shipment_id,
                'driver_id' => $doc->driver_id,
                'driver_name' => $doc->driver?->firstname . ' ' . $doc->driver?->lastname,
                'document_type' => $doc->document_type,
                'file_path' => asset('storage/' . $doc->file_path),
                'extracted_fields' => $doc->extracted_fields,
                'extraction_status' => $doc->extraction_status,
                'extraction_confidence' => $doc->extraction_confidence,
                'extracted_at' => $doc->extracted_at,
                'created_at' => $doc->created_at,
                'pickup_address' => $doc->shipment?->pickup_address,
                'drop_address' => $doc->shipment?->drop_address,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'pagination' => [
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
            ],
        ]);
    }

    /**
     * Get single document details
     */
    public function getDocument(int $id): JsonResponse
    {
        $document = ShipmentDocument::with(['shipment', 'driver'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $document->id,
                'shipment_id' => $document->shipment_id,
                'driver_id' => $document->driver_id,
                'driver_name' => $document->driver?->firstname . ' ' . $document->driver?->lastname,
                'document_type' => $document->document_type,
                'file_path' => asset('storage/' . $document->file_path),
                'extracted_fields' => $document->extracted_fields,
                'extraction_status' => $document->extraction_status,
                'extraction_confidence' => $document->extraction_confidence,
                'extracted_at' => $document->extracted_at,
                'created_at' => $document->created_at,
                'shipment' => [
                    'id' => $document->shipment?->id,
                    'pickup_address' => $document->shipment?->pickup_address,
                    'drop_address' => $document->shipment?->drop_address,
                    'status' => $document->shipment?->status,
                    'distance_miles' => $document->shipment?->distance_miles,
                ],
            ],
        ]);
    }

    /**
     * Get document statistics
     */
    public function getDocumentStats(): JsonResponse
    {
        $totalBOL = ShipmentDocument::where('document_type', 'BOL')->count();
        $totalPOD = ShipmentDocument::where('document_type', 'POD')->count();
        $pendingBOL = ShipmentDocument::where('document_type', 'BOL')
            ->where('extraction_status', 'pending')
            ->count();
        $pendingPOD = ShipmentDocument::where('document_type', 'POD')
            ->where('extraction_status', 'pending')
            ->count();
        $failedExtraction = ShipmentDocument::where('extraction_status', 'failed')->count();

        $avgConfidence = ShipmentDocument::where('extraction_status', 'extracted')
            ->whereNotNull('extraction_confidence')
            ->avg('extraction_confidence');

        return response()->json([
            'success' => true,
            'stats' => [
                'total_bol' => $totalBOL,
                'total_pod' => $totalPOD,
                'total_documents' => $totalBOL + $totalPOD,
                'pending_bol' => $pendingBOL,
                'pending_pod' => $pendingPOD,
                'total_pending' => $pendingBOL + $pendingPOD,
                'failed_extraction' => $failedExtraction,
                'avg_ocr_confidence' => round($avgConfidence, 2),
            ],
        ]);
    }
}
