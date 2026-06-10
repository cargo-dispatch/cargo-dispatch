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
        Schema::table('shipments', function (Blueprint $table) {
              $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
        $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null'); // assuming drivers are users
    });
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
          $table->dropForeign(['vehicle_id']);
        $table->dropForeign(['driver_id']);
        $table->dropColumn(['vehicle_id', 'driver_id']);
        });
    }
};
