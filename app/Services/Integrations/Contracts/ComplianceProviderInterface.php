<?php

namespace App\Services\Integrations\Contracts;

interface ComplianceProviderInterface
{
    /**
     * Look up carrier safety data by DOT number.
     * Returns legal_name, safety_rating, out_of_service_pct, inspections, crashes.
     */
    public function getCarrierInfo(string $dotNumber): array;

    /**
     * Check if a carrier's operating authority is active.
     * Returns ['active' => bool, 'authority_type' => string, 'insurance_on_file' => bool]
     */
    public function checkAuthority(string $dotNumber): array;
}
