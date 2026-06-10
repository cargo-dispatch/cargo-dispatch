<?php

namespace App\Models\ShipmentInvoices;

use App\Models\Drivers\Driver;
use App\Models\Shipments\Shipment;
use Illuminate\Database\Eloquent\Model;

class ShipmentInvoice extends Model
{
     protected $fillable = [
        'shipment_id',
        'miles_per_gallon',
        'fuel_price',
        'fuel_cost',
        'driver_pay',
        'driver_cost',
        'insurance_per_mile',
        'insurance_cost',
        'maintenance_per_mile',
        'maintenance_cost',
        'overhead_per_mile',
        'overhead_cost',
        'ifta_per_mile',
        'ifta_cost',
        'tolls_fee',
        'profit_percentage',
        'extra_charges',
        'invoice_note',
        'total_cost',
        'total_with_profit',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
     public function associatedDrivers()
    {
        return $this->belongsToMany(
            Driver::class,
            'shipment_associated_drivers',
            'shipment_id',
            'driver_id'
        )->withTimestamps();
    }
}
