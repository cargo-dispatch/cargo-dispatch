<?php

namespace App\Models\ShipmentDocuments;

use App\Models\Drivers\Driver;
use App\Models\Shipments\Shipment;
use Illuminate\Database\Eloquent\Model;

class ShipmentDocument extends Model
{
    protected $fillable = [
        'shipment_id',
        'driver_id',
        'document_type',
        'file_path',
        'extracted_fields',
        'extraction_status',
        'extraction_confidence',
        'extracted_at',
        'error_message',
        'notes',
    ];

    protected $casts = [
        'extracted_fields' => 'array',
        'extracted_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

