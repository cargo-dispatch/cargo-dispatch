<?php

namespace App\Models\Drivers;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverCredential extends Model 
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'driver_id',
        'title',
        'expiry_date',
        'file',
    ];

    

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
