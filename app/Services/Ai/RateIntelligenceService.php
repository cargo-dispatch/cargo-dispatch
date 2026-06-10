<?php

namespace App\Services\Ai;

use App\Services\FreightFinderScraper;
use Illuminate\Support\Facades\Cache;

class RateIntelligenceService
{
    public function __construct(
        protected GeminiClient $groq,
        protected FreightFinderScraper $scraper
    ) {}

    /**
     * Analyse rates for all scraped loads.
     * Returns per-lane stats + Groq insight for each lane.
     */
    public function getLaneIntelligence(string $origin = 'Dallas, TX', int $pages = 2): array
    {
        return Cache::remember('rate_intel_' . md5($origin . $pages), 600, function () use ($origin, $pages) {
            $loads = $this->scraper->getLoads($origin, 500, $pages);
            return $this->analyse($loads);
        });
    }

    private function extractState(string $cityState): string
    {
        $parts = explode(',', $cityState);
        return isset($parts[1]) ? trim($parts[1]) : trim($parts[0]);
    }

    private function analyse(array $loads): array
    {
        // Group by origin STATE → destination STATE for meaningful aggregation
        $lanes = [];
        foreach ($loads as $load) {
            if (empty($load['origin']) || empty($load['destination'])) continue;

            $oState = $this->extractState($load['origin']);
            $dState = $this->extractState($load['destination']);
            if (!$oState || !$dState) continue;

            $lane = $oState . ' → ' . $dState;

            if (!isset($lanes[$lane])) {
                $lanes[$lane] = ['lane' => $lane, 'rates' => [], 'loads' => []];
            }

            $lanes[$lane]['loads'][] = $load;
            $rate = $this->parseRate($load['rate'] ?? '');
            if ($rate > 0) $lanes[$lane]['rates'][] = $rate;
        }

        // Compute stats per lane
        $results = [];
        foreach ($lanes as $lane => $data) {
            $rates = $data['rates'];
            $count = count($data['loads']);
            $equips = array_values(array_unique(array_filter(array_column($data['loads'], 'equipment'))));

            $results[] = [
                'lane'        => $lane,
                'load_count'  => $count,
                'avg_rate'    => count($rates) ? round(array_sum($rates) / count($rates), 2) : null,
                'min_rate'    => count($rates) ? min($rates) : null,
                'max_rate'    => count($rates) ? max($rates) : null,
                'has_rates'   => count($rates) > 0,
                'equipment'   => $equips,
                'loads'       => array_slice($data['loads'], 0, 3),
                'insight'     => null,
            ];
        }

        // Sort by load count desc, take top 15 lanes
        usort($results, fn($a, $b) => $b['load_count'] <=> $a['load_count']);
        $results = array_slice($results, 0, 15);

        // Get Groq insight for top 10 lanes
        $topLanes = array_slice($results, 0, 10);
        if (!empty($topLanes)) {
            $insights = $this->getGroqInsights($topLanes);
            foreach ($results as &$r) {
                $r['insight'] = $insights[$r['lane']] ?? null;
            }
        }

        return $results;
    }

    private function getGroqInsights(array $lanes): array
    {
        $laneData = array_map(fn($l) => [
            'lane'        => $l['lane'],
            'load_count'  => $l['load_count'],
            'equipment'   => $l['equipment'] ?? [],
        ], $lanes);

        $prompt = <<<PROMPT
You are a US freight dispatch rate analyst with deep knowledge of trucking lane markets.

For each lane below write a 1-sentence recommendation (10-18 words) telling a dispatcher whether to take the load, negotiate, or hold — based on lane geography and load count.

Rules:
- Every value MUST be a full sentence, never a single word like "Take" or "Hold".
- Mention the lane direction or equipment type in the sentence.
- End with a clear action: "take it", "negotiate the rate", or "hold for better offer".

Return ONLY valid JSON. Keys must exactly match the lane strings provided. No markdown, no code fences.
Example: {"DALLAS,TX → CHICAGO,IL": "Strong Midwest lane with high demand, take it now to fill capacity."}

Lane data:
PROMPT;

        try {
            $response = $this->groq->generateContent([[
                'role'  => 'user',
                'parts' => [
                    ['text' => $prompt],
                    ['text' => json_encode($laneData)],
                ],
            ]]);

            $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

            // Strip markdown code fences if present
            $text    = preg_replace('/^```json\s*/i', '', trim($text));
            $text    = preg_replace('/```$/', '', $text);
            $decoded = json_decode(trim($text), true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RateIntelligence Groq failed', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'groq_key_set' => !empty(config('services.groq.key')),
                'groq_model' => config('services.groq.model'),
            ]);
            return [];
        }
    }

    private function parseRate(string $rate): float
    {
        // Extract numeric value from "$700.00" or "700"
        preg_match('/[\d,]+\.?\d*/', str_replace(',', '', $rate), $m);
        return isset($m[0]) ? (float) $m[0] : 0.0;
    }
}
