<?php

namespace App\Models\DriverType;

use App\Models\Drivers\Driver;
use App\Models\ManageDriver\ManageDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class DriverType extends Model implements AuditableContract
{
    use Auditable,SoftDeletes;

    protected $fillable = ['name'];

    protected $table = 'driver_types';

    public function drivers()
    {
        return $this->hasMany(Driver::class, 'drivertype', 'id');
    }
}
