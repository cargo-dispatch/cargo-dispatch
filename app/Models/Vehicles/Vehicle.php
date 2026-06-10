<?php

namespace App\Models\Vehicles;

use App\Models\Drivers\Driver;
use App\Models\Shipments\Shipment;
use App\Models\VehicleAssignment\VehicleAssignment;
use App\Models\VehicleType\VehicleType;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Vehicle extends Model implements AuditableContract
{
    use HasRoles,Auditable,SoftDeletes;
    protected $fillable = [
        'vehicle_id',
        'license_plate_number',
        'vin',
        'make_model',
        'year_of_manufacture',
        'vehicle_type_id',
        'color',
        'fuel_type',
        'status',
        'ownership_status',
        'cargo_weight',
        'cargo_volume',
        'load_type_compatibility',
        'registration_expiry_date',
        'insurance_details',
        'insurance_expiry_date',
    ];

 
 public function assignments()
    {
        return $this->hasMany(VehicleAssignment::class);
    }
    public function vehicleType()
{
    return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
}

public function shipments()
{
    return $this->hasMany(Shipment::class, 'vehicle_id');
}

   public function vehicleAssignments()
    {
        return $this->hasMany(VehicleAssignment::class, 'vehicle_id');
    }
// In App\Models\Vehicles\Vehicle.php

public function vehicleAssignment()
{
    return $this->hasOne(VehicleAssignment::class, 'vehicle_id', 'id');
}

public function primaryDriver()
{
    return $this->hasOne(Driver::class, 'primary_vehicle_id');
}
}
