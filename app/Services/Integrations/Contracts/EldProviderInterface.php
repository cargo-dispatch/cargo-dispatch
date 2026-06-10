<?php

namespace App\Services\Integrations\Contracts;

use Illuminate\Support\Collection;

interface EldProviderInterface
{
    /**
     * Return live or simulated driver HOS / duty / location data.
     */
    public function getDriverStatuses(): Collection;

    /**
     * Return live or simulated truck locations and movement data.
     */
    public function getTruckLocations(): Collection;
}

