<?php

namespace App\Services\Integrations\Contracts;

interface DocumentAiProviderInterface
{
    /**
     * Extract structured fields from a logistics document (rate con, BOL, POD, invoice).
     *
     * @param  string  $path  Absolute or storage path to the file
     * @return array<string, mixed>
     */
    public function extractFields(string $path): array;
}

