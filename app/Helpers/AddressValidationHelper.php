<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class AddressValidationHelper
{
    public static function validateAddress($address)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;

        $response = Http::get($url);

        if (!$response->successful()) {
            return ['valid' => false, 'message' => 'Unable to connect to Google Maps API'];
        }

        $data = $response->json();

        if ($data['status'] === 'OK') {
            $result = $data['results'][0];
            return [
                'valid' => true,
                'formatted_address' => $result['formatted_address'],
                'lat' => $result['geometry']['location']['lat'],
                'lng' => $result['geometry']['location']['lng']
            ];
        }

        return [
            'valid' => false,
            'message' => 'Invalid address. Please enter a valid location.'
        ];
    }
}
