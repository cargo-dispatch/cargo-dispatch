<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceHelper 
{
    public static function getDistanceFromGoogle($fromAddress, $toAddress) 
    {
        $apiKey = config('services.google.maps_api_key'); // Use config instead of env
        Log::info('API key is', ['key' => $apiKey,$fromAddress,$toAddress]);
        
        if (!$apiKey) {
            Log::error('Google Maps API key not found in config');
            return null;
        }
        
        // Clean and validate addresses
        $fromAddress = trim($fromAddress);
        $toAddress = trim($toAddress);
        
        if (empty($fromAddress) || empty($toAddress)) {
            Log::error('Empty addresses provided', [
                'from' => $fromAddress,
                'to' => $toAddress
            ]);
            return null;
        }
        
        // Add country if not specified (helps with geocoding)
        if (!self::containsCountry($fromAddress)) {
            $fromAddress .= ', USA';
        }
        if (!self::containsCountry($toAddress)) {
            $toAddress .= ', USA';
        }
        
        Log::info('Attempting distance calculation', [
            'from' => $fromAddress,
            'to' => $toAddress
        ]);
        
        try {
            $response = Http::timeout(60)->get("https://maps.googleapis.com/maps/api/distancematrix/json", [
                'origins' => $fromAddress,
                'destinations' => $toAddress,
                'key' => $apiKey,
                'units' => 'metric',
                'avoid' => '', // Don't avoid anything
                'mode' => 'driving'
            ]);
            
            // Log the raw response for debugging
            Log::info('Google Distance API Response:', [
                'status_code' => $response->status(),
                'response' => $response->json()
            ]);
            
            if (!$response->successful()) {
                Log::error('Google API HTTP call failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
            
            $data = $response->json();
            
            // Check for API errors
            if (isset($data['error_message'])) {
                Log::error('Google API Error: ' . $data['error_message']);
                return null;
            }
            
            // Check status
            if ($data['status'] !== 'OK') {
                Log::error('Google API Status Error', [
                    'status' => $data['status'],
                    'data' => $data
                ]);
                return null;
            }
            
            // Check if we have the expected structure
            if (isset($data['rows'][0]['elements'][0])) {
                $element = $data['rows'][0]['elements'][0];
                
                Log::info('Processing element', [
                    'status' => $element['status'],
                    'element' => $element
                ]);
                
                // Handle different element statuses
                switch ($element['status']) {
                    case 'OK':
                        // Distance found successfully
                        if (isset($element['distance'])) {
                            $distanceKm = $element['distance']['value'] / 1000;
                            $distanceText = $element['distance']['text'];
                            
                            Log::info('Distance calculated successfully', [
                                'from' => $fromAddress,
                                'to' => $toAddress,
                                'distance_km' => $distanceKm,
                                'distance_text' => $distanceText
                            ]);
                            
                            return [
                                'kilometers' => round($distanceKm, 2),
                                'text' => $distanceText
                            ];
                        }
                        break;
                        
                    case 'ZERO_RESULTS':
                        Log::warning('ZERO_RESULTS: No driving route found', [
                            'from' => $fromAddress,
                            'to' => $toAddress,
                            'suggestion' => 'Try more specific addresses with street numbers, city, state, and country'
                        ]);
                        
                        // Try geocoding each address separately to check if they're valid
                        $fromGeocode = self::geocodeAddress($fromAddress, $apiKey);
                        $toGeocode = self::geocodeAddress($toAddress, $apiKey);
                        
                        if (!$fromGeocode) {
                            Log::error('Pickup address could not be geocoded', ['address' => $fromAddress]);
                        }
                        if (!$toGeocode) {
                            Log::error('Drop address could not be geocoded', ['address' => $toAddress]);
                        }
                        
                        // Return null for ZERO_RESULTS to indicate failure
                        return null;
                        
                    case 'NOT_FOUND':
                        Log::error('NOT_FOUND: One or both addresses not found', [
                            'from' => $fromAddress,
                            'to' => $toAddress
                        ]);
                        return null;
                        
                    case 'MAX_ROUTE_LENGTH_EXCEEDED':
                        Log::error('Route is too long', [
                            'from' => $fromAddress,
                            'to' => $toAddress
                        ]);
                        return null;
                        
                    default:
                        Log::error('Unknown element status', [
                            'status' => $element['status'],
                            'from' => $fromAddress,
                            'to' => $toAddress,
                            'element' => $element
                        ]);
                        return null;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Exception in distance calculation', [
                'error' => $e->getMessage(),
                'from' => $fromAddress,
                'to' => $toAddress
            ]);
            return null;
        }
        
        Log::error('Distance data not found in response', [
            'from' => $fromAddress,
            'to' => $toAddress,
            'response' => $data ?? 'No response data'
        ]);
        
        return null;
    }
    
    /**
     * Check if address contains country information
     */
    private static function containsCountry($address) 
    {
        $countries = ['USA', 'US', 'Canada', 'CA', 'Mexico', 'MX', 'United States', 'United Kingdom', 'UK'];
        $upperAddress = strtoupper($address);
        
        foreach ($countries as $country) {
            if (strpos($upperAddress, strtoupper($country)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Geocode a single address to check if it's valid
     */
    private static function geocodeAddress($address, $apiKey) 
    {
        try {
            $response = Http::timeout(15)->get("https://maps.googleapis.com/maps/api/geocode/json", [
                'address' => $address,
                'key' => $apiKey
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return isset($data['results'][0]) && $data['status'] === 'OK';
            }
            
        } catch (\Exception $e) {
            Log::error('Geocoding failed', ['address' => $address, 'error' => $e->getMessage()]);
        }
        
        return false;
    }
}