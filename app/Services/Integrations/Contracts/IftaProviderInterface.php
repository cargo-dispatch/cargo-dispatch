<?php

namespace App\Services\Integrations\Contracts;

interface IftaProviderInterface
{
    /**
     * Calculate miles driven per state for a vehicle in a date range.
     * Returns [['state' => 'TX', 'miles' => 423.5, 'gallons_used' => 62.1], ...]
     */
    public function getMilesByState(int $vehicleId, string $dateFrom, string $dateTo): array;

    /**
     * Generate a quarterly IFTA fuel tax report.
     * Returns total miles, total gallons, tax owed per state.
     */
    public function generateQuarterlyReport(int $vehicleId, int $year, int $quarter): array;
}
