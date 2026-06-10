<?php

namespace App\Models\Shipments;

use App\Models\AssociatedDriver\AssociatedDriver;
use App\Models\Customers\Customer;
use App\Models\Drivers\Driver;
use App\Models\Remarks\Remarks;
use App\Models\ShipmentInvoices\ShipmentInvoice;
use App\Models\ShipmentDocuments\ShipmentDocument;
use App\Models\User;
use App\Models\Vehicles\Vehicle;
use App\Models\VehicleType\VehicleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;   

class Shipment extends Model implements AuditableContract
{
    use Auditable,Notifiable,SoftDeletes,HasApiTokens; 

    protected $fillable = [
        'customer_id',
        'vehicle_type_id',
        'vehicle_id',
        'weight',
        'volume',
        'pallets',
        'pickup_address',
        'drop_address',
        'pickup_time',
        'delivery_time',
        'special_instructions',
        'estimated_cost',
        'distance_km',
       'distance_miles',
       'distance_text',
        'status',
        'driver_id',
        'createdBy',
        'equipment_required',
        'load_type',
        'reference_number',
        'pickup_contact_name',
        'pickup_contact_phone',
        'delivery_contact_name',
        'delivery_contact_phone',
        'deadhead_miles',
        'detention_hours',
        'lumper_fee',
        'per_diem_days',
        'scale_fees',
        'tarp_required',
        'permit_fee',
    ];

    protected $casts = [
        'equipment_required' => 'array',
      'pickup_time' => 'datetime',
    'delivery_time' => 'datetime',
    ];

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    // ✅ FIXED: Correct foreign key relationship
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function drivers()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }
   public function remarks()
{
    return $this->hasMany(Remarks::class); // no morph, just regular relation via shipment_id
}
public function shipmentInvoice()
{
    return $this->hasMany(ShipmentInvoice::class, 'shipment_id');
}
public function associatedDrivers()
{
    return $this->hasMany(AssociatedDriver::class, 'shipment_id');
}

public function documents()
{
    return $this->hasMany(ShipmentDocument::class, 'shipment_id');
}


}