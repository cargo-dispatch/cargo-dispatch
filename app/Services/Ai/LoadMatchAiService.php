<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;

class LoadMatchAiService
{
    public function __construct(
        protected GeminiClient $client
    ) {
    }

    /**
     * Ask Gemini to score and rank candidate (driver, vehicle) matches for a load.
     *
     * @param  array<string, mixed>  $payload  Shape: ['load' => [...], 'candidates' => [...]]
     * @return array<int, array<string, mixed>> Each element: ['candidate_id' => string, 'score' => int, 'reason' => string]
     */
    public function rankCandidates(array $payload): array
    {
        $loadId = $payload['load']['id'] ?? ($payload['load']['external_load_id'] ?? 'unknown');
        $disableKey = 'ai_match_disable_' . (string) $loadId;
        $disabledUntilTs = Cache::get($disableKey);
        if (is_int($disabledUntilTs) && $disabledUntilTs > now()->timestamp) {
            return $this->heuristicRank($payload);
        }

        $instructions = <<<PROMPT
You are an experienced US truck dispatch planner.
You receive JSON describing ONE load and multiple candidate driver/vehicle options.

Each candidate includes current_lat/current_lng (GPS position of the truck right now).
The load includes pickup_address and drop_address.

Your task — score each candidate 0–100 (higher = better) based on:
1. Geographic proximity: use the candidate's GPS coordinates vs the pickup city to estimate deadhead miles. Closer = higher score.
2. Hours of Service: hos_drive_remaining_minutes must cover the trip. Low HOS = penalty.
3. Equipment match: candidate equipment must match load equipment type.
4. Driver availability: current_duty_status "driving" or "on_duty_not_driving" = slight penalty; "off_duty" = available.
5. Vehicle status: "busy" = heavy penalty.

Write a SHORT reason (1–2 sentences) mentioning: estimated deadhead, HOS adequacy, and equipment fit.

Rules:
- Return ONLY a JSON array.
- Each element: {"candidate_id": "...", "score": 0-100, "reason": "..."}.
- Never invent candidate_ids — use exactly the ids from the input.
PROMPT;

        $contents = [
            [
                'role'  => 'user',
                'parts' => [
                    ['text' => $instructions],
                    ['text' => 'INPUT JSON: ' . json_encode($payload)],
                ],
            ],
        ];

        try {
            $raw = $this->client->generateContent($contents);

            $text = $raw['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
            $decoded = json_decode($text, true);

            if (!is_array($decoded)) {
                return $this->heuristicRank($payload);
            }

            return array_values(array_filter($decoded, fn ($row) => isset($row['candidate_id'])));
        } catch (RequestException $e) {
            $status = method_exists($e, 'response') && $e->response ? $e->response->status() : null;

            // Gemini 429/quota exceeded: disable AI calls temporarily for this load
            if ($status === 429) {
                Cache::put($disableKey, now()->addMinutes(10)->timestamp, now()->addMinutes(10));
            }

            return $this->heuristicRank($payload);
        } catch (\Throwable $e) {
            return $this->heuristicRank($payload);
        }
    }

    /**
     * Cheap local ranking so the UI still works when Gemini is unavailable.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function heuristicRank(array $payload): array
    {
        $load          = $payload['load'] ?? [];
        $loadEquipment = strtolower(trim((string) ($load['equipment'] ?? '')));
        $pickupAddress = (string) ($load['pickup_address'] ?? '');
        $pickupCoords  = $this->cityCoords($pickupAddress);

        $candidates = $payload['candidates'] ?? [];
        if (!is_array($candidates) || !$candidates) {
            return [];
        }

        $ranked = [];

        foreach ($candidates as $candidate) {
            $candidateId = $candidate['candidate_id'] ?? null;
            if (!$candidateId) continue;

            $hosDrive      = (int) ($candidate['hos_drive_remaining_minutes'] ?? 0);
            $candEquipment = strtolower(trim((string) ($candidate['equipment'] ?? '')));
            $status        = (string) ($candidate['vehicle_status'] ?? '');
            $dutyStatus    = (string) ($candidate['current_duty_status'] ?? '');

            // Real distance if truck has GPS coords and pickup city is known
            $truckLat = $candidate['current_lat'] ?? null;
            $truckLng = $candidate['current_lng'] ?? null;
            if ($truckLat && $truckLng && $pickupCoords) {
                $distance = $this->haversine((float)$truckLat, (float)$truckLng, $pickupCoords[0], $pickupCoords[1]);
            } else {
                $distance = 300; // unknown — assume moderate deadhead
            }

            $equipmentMatch = ($loadEquipment && $candEquipment)
                ? (str_contains($candEquipment, $loadEquipment) || str_contains($loadEquipment, $candEquipment) ? 1 : 0)
                : 0;

            $score = 70;
            $score -= min(40, $distance * 0.15);          // up to -40 for far trucks
            $score += min(20, $hosDrive / 36);            // up to +20 for full HOS
            $score += $equipmentMatch * 15;               // +15 for equipment match
            if ($status === 'busy')           $score -= 40;
            if ($dutyStatus === 'off_duty')   $score += 5;
            if ($dutyStatus === 'driving')    $score -= 5;

            $score = max(0, min(100, (int) round($score)));

            $hosHours    = round($hosDrive / 60, 1);
            $distLabel   = $distance < 290 ? round($distance) . ' mi deadhead' : 'unknown location';
            $equipLabel  = $equipmentMatch ? 'equipment matches' : 'equipment mismatch';
            $hosLabel    = $hosHours >= 8 ? "{$hosHours}h HOS available" : "only {$hosHours}h HOS — may be tight";

            $ranked[] = [
                'candidate_id' => (string) $candidateId,
                'score'        => $score,
                'reason'       => ucfirst("{$distLabel}; {$hosLabel}; {$equipLabel}."),
            ];
        }

        usort($ranked, fn ($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return $ranked;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 3958.8; // Earth radius in miles
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function cityCoords(string $address): ?array
    {
        $cities = [
            'dallas'       => [32.7767, -96.7970],
            'houston'      => [29.7604, -95.3698],
            'austin'       => [30.2672, -97.7431],
            'san antonio'  => [29.4241, -98.4936],
            'los angeles'  => [34.0522, -118.2437],
            'chicago'      => [41.8781, -87.6298],
            'new york'     => [40.7128, -74.0060],
            'phoenix'      => [33.4484, -112.0740],
            'philadelphia' => [39.9526, -75.1652],
            'san diego'    => [32.7157, -117.1611],
            'jacksonville' => [30.3322, -81.6557],
            'san jose'     => [37.3382, -121.8863],
            'detroit'      => [42.3314, -83.0458],
            'atlanta'      => [33.7490, -84.3880],
            'nashville'    => [36.1627, -86.7816],
            'miami'        => [25.7617, -80.1918],
            'seattle'      => [47.6062, -122.3321],
            'denver'       => [39.7392, -104.9903],
            'boston'       => [42.3601, -71.0589],
            'memphis'      => [35.1495, -90.0490],
            'charlotte'    => [35.2271, -80.8431],
            'louisville'   => [38.2527, -85.7585],
            'kansas city'  => [39.0997, -94.5786],
            'columbus'     => [39.9612, -82.9988],
            'indianapolis' => [39.7684, -86.1581],
            'oklahoma city'=> [35.4676, -97.5164],
            'el paso'      => [31.7619, -106.4850],
            'portland'     => [45.5051, -122.6750],
            'las vegas'    => [36.1699, -115.1398],
            'albuquerque'  => [35.0844, -106.6504],
            'tucson'       => [32.2226, -110.9747],
            'fresno'       => [36.7378, -119.7871],
            'sacramento'   => [38.5816, -121.4944],
            'minneapolis'  => [44.9778, -93.2650],
            'omaha'        => [41.2565, -95.9345],
            'cleveland'    => [41.4993, -81.6944],
            'pittsburgh'   => [40.4406, -79.9959],
            'richmond'     => [37.5407, -77.4360],
            'st. louis'    => [38.6270, -90.1994],
            'st louis'     => [38.6270, -90.1994],
            'new orleans'  => [29.9511, -90.0715],
            'tampa'        => [27.9506, -82.4572],
            'orlando'      => [28.5383, -81.3792],
            'birmingham'   => [33.5186, -86.8104],
            'salt lake city'=> [40.7608, -111.8910],
            'raleigh'      => [35.7796, -78.6382],
            'fort myers'   => [26.6406, -81.8723],
            'fort worth'   => [32.7555, -97.3308],
            'fort lauderdale' => [26.1224, -80.1373],
            'baton rouge'  => [30.4515, -91.1871],
            'little rock'  => [34.7465, -92.2896],
            'columbia'     => [34.0007, -81.0348],
            'jackson'      => [32.2988, -90.1848],
            'montgomery'   => [32.3614, -86.2792],
            'mobile'       => [30.6954, -88.0399],
            'pensacola'    => [30.4213, -87.2169],
            'shreveport'   => [32.5252, -93.7502],
            'amarillo'     => [35.2220, -101.8313],
            'lubbock'      => [33.5779, -101.8552],
            'corpus christi' => [27.8006, -97.3964],
            'laredo'       => [27.5306, -99.4803],
            'wichita'      => [37.6872, -97.3301],
            'tulsa'        => [36.1540, -95.9928],
            'spokane'      => [47.6587, -117.4260],
            'boise'        => [43.6150, -116.2023],
            'billings'     => [45.7833, -108.5007],
            'fargo'        => [46.8772, -96.7898],
            'sioux falls'  => [43.5473, -96.7283],
            'des moines'   => [41.5868, -93.6250],
            'madison'      => [43.0731, -89.4012],
            'green bay'    => [44.5133, -88.0133],
            'milwaukee'    => [43.0389, -87.9065],
            'grand rapids' => [42.9634, -85.6681],
            'toledo'       => [41.6528, -83.5379],
            'akron'        => [41.0814, -81.5190],
            'buffalo'      => [42.8864, -78.8784],
            'rochester'    => [43.1566, -77.6088],
            'hartford'     => [41.7658, -72.6851],
            'providence'   => [41.8240, -71.4128],
            'norfolk'      => [36.8508, -76.2859],
            'virginia beach' => [36.8529, -75.9780],
            'greensboro'   => [36.0726, -79.7920],
            'winston-salem' => [36.0999, -80.2442],
            'durham'       => [35.9940, -78.8986],
            'savannah'     => [32.0835, -81.0998],
            'augusta'      => [33.4735, -82.0105],
            'knoxville'    => [35.9606, -83.9207],
            'chattanooga'  => [35.0456, -85.3097],
            'lexington'    => [38.0406, -84.5037],
            'cincinnati'   => [39.1031, -84.5120],
            'dayton'       => [39.7589, -84.1916],
            'springfield'  => [37.2153, -93.2982],
            'st. paul'     => [44.9537, -93.0900],
            'tacoma'       => [47.2529, -122.4443],
            'bakersfield'  => [35.3733, -119.0187],
            'stockton'     => [37.9577, -121.2908],
            'riverside'    => [33.9533, -117.3961],
            'anaheim'      => [33.8366, -117.9143],
            'long beach'   => [33.7701, -118.1937],
        ];

        $addr = strtolower($address);
        foreach ($cities as $city => $coords) {
            if (str_contains($addr, $city)) {
                return $coords;
            }
        }
        return null;
    }
}

