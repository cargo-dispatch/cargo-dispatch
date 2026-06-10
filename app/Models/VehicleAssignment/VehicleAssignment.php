<?php

namespace App\Models\VehicleAssignment;

use App\Models\Drivers\Driver;
use App\Models\Shipments\Shipment;
use App\Models\Vehicles\Vehicle;
use Illuminate\Database\Eloquent\Model;

class VehicleAssignment extends Model
{
    protected $fillable = ['driver_id', 'vehicle_id'];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function shipments()
{
    return $this->hasMany(Shipment::class, 'vehicle_id');
}

}
