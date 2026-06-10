<?php

namespace App\Models\Drivers;

use App\Models\DriverType\DriverType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DriverInvitation extends Model
{
    protected $fillable = [
        'driver_id', 'email', 'token', 'firstname', 'lastname',
        'phoneno', 'driver_type_id', 'created_by', 'expires_at', 'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function driverType()
    {
        return $this->belongsTo(DriverType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }
}
