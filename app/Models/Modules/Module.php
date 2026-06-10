<?php

namespace App\Models\Modules;

use App\Models\Role\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
   
    protected $fillable = ['name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'module_role')
                    ->withPivot('add', 'edit', 'delete', 'view')
                    ->withTimestamps();
    }

}
