<?php

namespace App\Services\Integrations;

abstract class BaseProvider
{
    /**
     * Returns true when a real API key is configured.
     * Subclasses set $this->apiKey in their constructor.
     */
    protected function isReal(): bool
    {
        return !empty($this->apiKey ?? '');
    }
}
