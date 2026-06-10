<?php

namespace App\Models\MaintenanceType;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;

class MaintenanceType extends Model  implements AuditableContract
{
     use Auditable,SoftDeletes;
    protected $table = 'maintenance_types';
    protected $fillable = ['maintenance_types'];
}
