<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FreightFinderScraper
{
    private const BASE     = 'https://www.freightfinder.com';
    private const PER_PAGE = 25;
    private const HEADERS  = [
        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
    ];

    public function getLoads(string $origin = 'Dallas, TX', int $radius = 500, int $pages = 4): array
    {
        $cacheKey = 'freightfinder_' . md5($origin . $radius . $pages);

        return Cache::remember($cacheKey, 300, function () use ($origin, $radius, $pages) {
            return $this->scrapeListingPages($origin, $radius, $pages);
        });
    }

    private function scrapeListingPages(string $origin, int $radius, int $pages): array
    {
        $loads   = [];
        $seenIds = [];

        for ($p = 0; $p < $pages; $p++) {
            $url = self::BASE . '/database/search/city-radius?' . http_build_query([
                'searchType'           => 'loads',
                'Equipment'            => '',
                'vchOrigin'            => $origin,
                'geoOrigin'            => '',
                'intOriginRadius'      => $radius,
                'vchDestination'       => '',
                'geoDestination'       => '',
                'intDestinationRadius' => $radius,
                'perPage'              => self::PER_PAGE,
                'row'                  => $p * self::PER_PAGE + 1,
                'vchUserAction'        => 'Search',
            ]);

            $response = Http::withHeaders(self::HEADERS)->timeout(20)->get($url);

            if (!$response->ok()) {
                usleep(500000);
                continue;
            }

            $html      = $response->body();
            $pageLoads = $this->parseListingPage($html, $seenIds);

            if (empty($pageLoads)) break; // no more results

            $loads = array_merge($loads, $pageLoads);

            // polite delay between pages
            if ($p < $pages - 1) usleep(400000);
        }

        return $loads;
    }

    private function parseListingPage(string $html, array &$seenIds): array
    {
        $loads = [];

        // Each load is a <tr> starting with data-label="Available"
        preg_match_all('/<tr>\s*<td[^>]*data-label="Available">(.*?)<\/tr>/s', $html, $rowMatches);

        foreach ($rowMatches[0] as $row) {
            // Load ID
            preg_match('/truck-load-detail\?searchType=loads&id=(\d+)/', $row, $idMatch);
            $id = $idMatch[1] ?? null;
            if (!$id || isset($seenIds[$id])) continue;
            $seenIds[$id] = true;

            // All labelled cells
            preg_match_all('/<td[^>]*data-label="([^"]+)"[^>]*>(.*?)<\/td>/s', $row, $cells);
            $data = [];
            foreach ($cells[1] as $i => $label) {
                $data[$label] = trim(strip_tags($cells[2][$i]));
            }

            // Company + phone are in the same Company cell — split on whitespace runs
            $companyRaw = $data['Company'] ?? '';
            $phone      = '';
            $company    = $companyRaw;
            if (preg_match('/(\d[\d\-\.\(\)\s]{6,}\d)\s*$/', $companyRaw, $phoneMatch)) {
                $phone   = preg_replace('/\D/', '', $phoneMatch[1]);
                $company = trim(str_replace($phoneMatch[0], '', $companyRaw));
            }

            // Origin / destination from map URL (cleaner than cell text)
            preg_match('/originname=([^&]+)&originlat=[^&]+&originlon=[^&]+&destinationname=([^&]+)&destinationlat/', $row, $routeMatch);

            $loads[] = [
                'id'          => $id,
                'date'        => $data['Available'] ?? '',
                'origin'      => isset($routeMatch[1]) ? urldecode($routeMatch[1]) : ($data['From'] ?? ''),
                'destination' => isset($routeMatch[2]) ? urldecode($routeMatch[2]) : ($data['To'] ?? ''),
                'equipment'   => $data['Equipment'] ?? '',
                'company'     => $company,
                'phone'       => $phone,
                'rate'        => '',  // not on listing page — loaded on row click
                'weight'      => '',  // not on listing page — loaded on row click
                'address'     => '',  // not on listing page — loaded on row click
            ];
        }

        return $loads;
    }

    // Called on-demand when user clicks a row
    public function getDetail(string $id): array
    {
        $cacheKey = 'ff_detail_' . $id;

        return Cache::remember($cacheKey, 1800, function () use ($id) {
            $url      = self::BASE . '/database/truck-load-detail?searchType=loads&id=' . $id;
            $response = Http::withHeaders(self::HEADERS)->timeout(15)->get($url);
            if (!$response->ok()) return [];

            $html = $response->body();

            return [
                'company'   => $this->extract($html, '/<td colspan="2" class="text-center display-5">\s*(.*?)\s*<\/td>/s'),
                'address'   => $this->extractAfterLabel($html, 'Address:'),
                'phone'     => $this->extractAfterLabel($html, 'Phone:'),
                'equipment' => $this->extractAfterLabel($html, 'Equipment:'),
                'weight'    => $this->extractAfterLabel($html, 'Weight:'),
                'rate'      => $this->extractAfterLabel($html, 'Rate:'),
            ];
        });
    }

    private function extract(string $html, string $pattern): string
    {
        preg_match($pattern, $html, $m);
        return isset($m[1]) ? trim(strip_tags($m[1])) : '';
    }

    private function extractAfterLabel(string $html, string $label): string
    {
        $escaped = preg_quote($label, '/');
        preg_match('/' . $escaped . '\s*<\/th>\s*<td[^>]*>(.*?)<\/td>/s', $html, $m);
        return isset($m[1]) ? trim(strip_tags($m[1])) : '';
    }
}
