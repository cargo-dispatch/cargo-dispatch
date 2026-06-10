<?php

namespace App\Models\GeneralSetting;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
        protected $fillable = ['fuel_price', 'company_profit'];

         protected $casts = [
        'fuel_price' => 'decimal:2',
        'company_profit' => 'decimal:2',
    ];

}
