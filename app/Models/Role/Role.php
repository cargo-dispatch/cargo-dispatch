<?php

namespace App\Models\Role;

use App\Models\Modules\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public function modules()
{
    return $this->belongsToMany(Module::class, 'module_role')
                ->withPivot('add', 'edit', 'delete', 'view')
                ->withTimestamps();
}

}
