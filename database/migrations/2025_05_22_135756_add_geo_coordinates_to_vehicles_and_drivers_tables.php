<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::table('vehicles', function (Blueprint $table) {
            $table->string('geo_coordinates')->default('0,0');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('geo_coordinates')->default('0,0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('geo_coordinates');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('geo_coordinates');
        });
    }
};
