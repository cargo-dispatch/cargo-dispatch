<?php

namespace App\Exports;

use App\Models\Shipment;
use App\Models\Shipments\Shipment as ShipmentsShipment;
use Maatwebsite\Excel\Concerns\FromCollection;

class ShipmentsExport implements FromCollection
{
    public function collection()
    {
        return ShipmentsShipment::all();
    }
}
