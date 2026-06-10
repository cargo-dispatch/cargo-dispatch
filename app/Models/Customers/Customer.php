<?php

namespace App\Models\Customers;

use App\Models\Remarks\Remarks;
use App\Models\Shipments\Shipment;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Laravel\Sanctum\HasApiTokens;

use OwenIt\Auditing\Auditable;

class Customer extends Authenticatable implements AuditableContract, CanResetPasswordContract
{
     use Auditable, SoftDeletes, HasApiTokens, Notifiable, CanResetPassword;
   protected $fillable = [
    'first_name',
    'last_name',
    'address1',
    'address2',
    'city',
    'state',
    'zip',
    'customer_title',
    'email',
    'phone',
    'password',
    'is_active',
];

protected $casts = [
    'is_active' => 'boolean',
];

public function shipments()
{
    return $this->hasMany(Shipment::class);
}
public function remarks()
{
    return $this->morphMany(Remarks::class, 'commenter');
}
 public function getAuthIdentifierName()
    {
        return 'id'; // or your primary key column name
    }

    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }


}
