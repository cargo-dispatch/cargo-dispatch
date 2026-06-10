<?php

namespace App\Models\Maintenance;

use App\Models\Drivers\Driver;
use App\Models\MaintenanceType\MaintenanceType;
use App\Models\Vehicles\Vehicle;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Database\Eloquent\SoftDeletes;

use OwenIt\Auditing\Auditable;



class Maintenance extends Model  implements AuditableContract
{
     use Auditable,SoftDeletes;
     protected $table = 'maintenance';
       protected $fillable = [
        'vehicle_id',
        'driver_id',
        'maintenance_type_id',
        'maintenance_date',
        'cost',
        'alert_status',
        'next_maintenance_date',
      'status',
        'next_maintenance_miles_reading', 
        'description',
    ];
protected $casts = [
    'maintenance_date' => 'date',
    'next_maintenance_date' => 'date',
];
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    // Relationship with Driver
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    // Relationship with MaintenanceType
    public function maintenanceType()
    {
        return $this->belongsTo(MaintenanceType::class, 'maintenance_type_id');
    }
   
}
