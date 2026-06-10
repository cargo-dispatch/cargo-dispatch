<?php

namespace App\Http\Controllers\Api;

use App\Events\LocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Vehicles\Vehicle;
use App\Services\Ai\LoadMatchAiService;
use App\Services\Integrations\Contracts\EldProviderInterface;
use App\Services\Integrations\Contracts\LoadBoardProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MockDataController extends Controller
{
    public function __construct(
        protected EldProviderInterface $eldProvider,
        protected LoadBoardProviderInterface $loadBoardProvider,
        protected LoadMatchAiService $aiService,
    ) {
    }

    /**
     * Return simulated driver HOS + truck locations for maps.
     */
    public function eldSnapshot(): JsonResponse
    {
        return response()->json([
            'drivers' => $this->eldProvider->getDriverStatuses(),
            'trucks'  => $this->eldProvider->getTruckLocations(),
        ]);
    }

    /**
     * Return a large, dynamic list of open loads from the mock board.
     */
    public function openLoads(): JsonResponse
    {
        return response()->json([
            'loads' => $this->loadBoardProvider->getOpenLoads(),
        ]);
    }

    /**
     * Simple test endpoint to exercise Gemini with fake candidates.
     */
    public function aiTest(Request $request): JsonResponse
    {
        $payload = $request->input('payload', [
            'load' => [
                'id' => 1,
                'pickup_city' => 'Dallas, TX',
                'delivery_city' => 'Atlanta, GA',
                'distance_miles' => 800,
                'pickup_time' => now()->addDay()->startOfDay()->toISOString(),
                'delivery_deadline' => now()->addDays(3)->endOfDay()->toISOString(),
                'weight_lbs' => 42000,
            ],
            'candidates' => [
                [
                    'candidate_id' => 'driver_1_vehicle_1',
                    'driver_name' => 'John Doe',
                    'distance_to_pickup_miles' => 15,
                    'hos_drive_remaining_minutes' => 480,
                    'equipment' => 'Dry Van',
                ],
                [
                    'candidate_id' => 'driver_2_vehicle_2',
                    'driver_name' => 'Sarah Lee',
                    'distance_to_pickup_miles' => 180,
                    'hos_drive_remaining_minutes' => 240,
                    'equipment' => 'Dry Van',
                ],
            ],
        ]);

        $ranking = $this->aiService->rankCandidates($payload);

        return response()->json([
            'input'   => $payload,
            'ranking' => $ranking,
        ]);
    }

}

