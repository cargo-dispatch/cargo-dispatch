<?php

namespace App\Services\Integrations\Contracts;

interface WeatherProviderInterface
{
    /**
     * Get weather conditions along a route.
     * Returns temperature, wind, precipitation, visibility per waypoint.
     */
    public function getRouteWeather(float $lat1, float $lng1, float $lat2, float $lng2): array;

    /**
     * Get active severe weather alerts for a US state code (e.g. 'TX').
     */
    public function getWeatherAlerts(string $stateCode): array;
}
