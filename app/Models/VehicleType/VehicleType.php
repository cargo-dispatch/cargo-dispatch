<?php

namespace App\Models\VehicleType;

use App\Models\Shipments\Shipment;
use App\Models\Vehicles\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class VehicleType extends Model implements AuditableContract
{
   use HasRoles,Auditable,SoftDeletes;
  protected $fillable = [
      'vehicle_type', 'image',
      'avg_fuel_efficiency',
      'driver_cost_per_mile',
      'insurance_per_mile',
      'maintenance_per_mile',
      'overhead_per_mile',
      'ifta_per_mile',
  ];
  protected $table ='vehicle_types';

  public function vehicles()
{
    return $this->hasMany(Vehicle::class);
}
public function shipments()
{
    return $this->hasMany(Shipment::class);
}


}
