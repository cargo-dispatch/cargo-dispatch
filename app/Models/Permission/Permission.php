<?php

namespace App\Models\Permission;

use App\Models\Modules\Module;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'guard_name', 'module_id'];
    public function module()
    
{
    return $this->belongsTo(Module::class);
}
}
