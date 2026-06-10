<?php

namespace App\Models\AssociatedDriver;

use App\Models\Drivers\Driver;
use App\Models\Shipments\Shipment;
use Illuminate\Database\Eloquent\Model;

class AssociatedDriver extends Model
{
    protected $table = 'shipment_associated_drivers';
    protected $fillable = ['shipment_id', 'driver_id'];

    public function shipments(){

return $this->belongsTo(Shipment::class, 'shipment_id');
    }
    public function drivers(){

        return $this->belongsTo(Driver::class,'driver_id');
    }
}
