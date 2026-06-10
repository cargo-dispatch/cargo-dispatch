<?php

namespace App\Services\Integrations\Contracts;

use Illuminate\Support\Collection;

interface LoadBoardProviderInterface
{
    /**
     * Get a collection of open loads from an external or mock board.
     */
    public function getOpenLoads(): Collection;

    /**
     * Post a new load to the external or mock board.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function postLoad(array $payload): array;
}

