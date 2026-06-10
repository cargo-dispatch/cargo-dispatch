<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->decimal('insurance_per_mile', 8, 4)->nullable()->default(0.10)->after('driver_cost_per_mile');
            $table->decimal('maintenance_per_mile', 8, 4)->nullable()->default(0.15)->after('insurance_per_mile');
            $table->decimal('overhead_per_mile', 8, 4)->nullable()->default(0.10)->after('maintenance_per_mile');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropColumn(['insurance_per_mile', 'maintenance_per_mile', 'overhead_per_mile']);
        });
    }
};
