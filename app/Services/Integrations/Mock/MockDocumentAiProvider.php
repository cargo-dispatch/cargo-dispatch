<?php

namespace App\Services\Integrations\Mock;

use App\Services\Integrations\Contracts\DocumentAiProviderInterface;

class MockDocumentAiProvider implements DocumentAiProviderInterface
{
    public function extractFields(string $path): array
    {
        // For now we just return deterministic but fake values so that
        // the rest of the document workflow can be developed.
        return [
            'document_type' => 'BOL',
            'fields' => [
                'shipper'         => 'Acme Foods',
                'consignee'       => 'XYZ Distribution',
                'pickup_city'     => 'Dallas',
                'pickup_state'    => 'TX',
                'delivery_city'   => 'Atlanta',
                'delivery_state'  => 'GA',
                'total_weight_lbs'=> 42000,
                'po_number'       => 'PO-' . date('ymd'),
                'reference_number'=> 'REF-' . substr(sha1($path), 0, 8),
            ],
            'confidence' => 0.95,
            'source_path'=> $path,
        ];
    }
}

