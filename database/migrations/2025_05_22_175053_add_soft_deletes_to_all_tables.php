<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToAllTables extends Migration
{
    public function up()
    {
        $tables = [
            'vehicle_types',
            'vehicles',
            'users',
            'shipments',
            'driver_types',
            'driver_credentials',
            'drivers',
            'customers',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes(); // adds 'deleted_at' nullable timestamp column
            });
        }
    }

    public function down()
    {
        $tables = [
            'vehicle_types',
            'vehicles',
            'users',
            'shipments',
            'driver_types',
            'driver_credentials',
            'drivers',
            'customers',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}