<?php

namespace App\Services\Geographic;

class GeoCalculator
{
    public static function haversineMiles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 3958.8;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // Geographic center of the contiguous US — used as fallback when no coordinates exist
    public const US_CENTER_LAT = 39.8283;
    public const US_CENTER_LNG = -98.5795;

    // Average truck highway speed used for duration estimates
    public const AVG_TRUCK_SPEED_MPH = 55;

    // Federal IFTA diesel tax rate (cents per gallon)
    public const IFTA_FEDERAL_TAX_RATE = 0.244;

    // US state bounding boxes [minLat, maxLat, minLng, maxLng] — used for IFTA state detection
    public const STATE_BOUNDS = [
        'TX' => [25.84, 36.50, -106.65, -93.51], 'CA' => [32.53, 42.01, -124.41, -114.13],
        'FL' => [24.55, 31.00, -87.63, -80.03],  'NY' => [40.50, 45.01, -79.76, -71.86],
        'IL' => [36.97, 42.51, -91.51, -87.02],  'GA' => [30.36, 35.00, -85.61, -80.84],
        'OH' => [38.40, 42.33, -84.82, -80.52],  'PA' => [39.72, 42.27, -80.52, -74.69],
        'MI' => [41.70, 48.19, -90.42, -82.41],  'TN' => [34.98, 36.68, -90.31, -81.65],
        'NC' => [33.84, 36.59, -84.32, -75.46],  'IN' => [37.77, 41.76, -88.10, -84.78],
        'MO' => [35.99, 40.61, -95.77, -89.10],  'CO' => [36.99, 41.00, -109.06, -102.04],
        'AZ' => [31.33, 37.00, -114.82, -109.05],'WA' => [45.54, 49.00, -124.73, -116.92],
        'MN' => [43.50, 49.38, -97.24, -89.49],  'KY' => [36.50, 39.15, -89.57, -81.96],
        'AL' => [30.14, 35.01, -88.47, -84.89],  'LA' => [28.93, 33.02, -94.04, -88.82],
        'OK' => [33.62, 37.00, -103.00, -94.43], 'KS' => [36.99, 40.00, -102.05, -94.59],
        'AR' => [33.00, 36.50, -94.62, -89.64],  'NE' => [40.00, 43.00, -104.05, -95.31],
        'NV' => [35.00, 42.00, -120.00, -114.04],'UT' => [37.00, 42.00, -114.05, -109.05],
        'NM' => [31.33, 37.00, -109.05, -103.00],'SC' => [32.03, 35.21, -83.35, -78.54],
        'MS' => [30.17, 35.01, -91.65, -88.10],  'WI' => [42.49, 47.08, -92.89, -86.25],
        'OR' => [41.99, 46.24, -124.57, -116.46],'VA' => [36.54, 39.46, -83.68, -75.23],
        'IA' => [40.37, 43.50, -96.64, -90.14],  'MD' => [37.91, 39.72, -79.49, -74.99],
        'WV' => [37.20, 40.64, -82.64, -77.72],  'SD' => [42.48, 45.94, -104.06, -96.44],
        'ND' => [45.94, 49.00, -104.05, -96.56], 'MT' => [44.36, 49.00, -116.05, -104.04],
        'ID' => [41.99, 49.00, -117.24, -111.04],'WY' => [40.99, 45.01, -111.06, -104.05],
    ];

    public static function stateFromCoords(float $lat, float $lng): string
    {
        foreach (self::STATE_BOUNDS as $state => [$minLat, $maxLat, $minLng, $maxLng]) {
            if ($lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng) {
                return $state;
            }
        }
        return 'XX'; // outside CONUS
    }
}
