<?php

namespace App\Models\Remarks;

use App\Models\Shipments\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Remarks extends Model
{

     protected $fillable = [
        'shipment_id',
        'commenter_id',
        'commenter_type',
        'comments'
    ];
public function shipment()
{
    return $this->belongsTo(Shipment::class);
}

public function commenter()
{
    return $this->morphTo(); // keep morph for Driver/Customer/User
}
    public function getCommenterModel()
    {
        if ($this->commenter_type === 'dispatcher') {
            return User::find($this->commenter_id);
        }

        return $this->commenter; // default behavior
    }

}
