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
        // Vehicles: live telematics / map tracking / ELD linkage
        Schema::table('vehicles', function (Blueprint $table) {
            // Current live position for maps (more precise than geo_coordinates string)
            $table->decimal('current_latitude', 10, 8)->nullable()->after('geo_coordinates');
            $table->decimal('current_longitude', 11, 8)->nullable()->after('current_latitude');

            // Movement & trip state for mock / real telematics
            $table->decimal('current_speed_mph', 6, 2)->nullable()->after('current_longitude');
            $table->decimal('current_heading_deg', 6, 2)->nullable()->after('current_speed_mph');
            $table->timestamp('last_location_update')->nullable()->after('current_heading_deg');

            // Target location for smoother simulated movement and routing
            $table->decimal('target_latitude', 10, 8)->nullable()->after('last_location_update');
            $table->decimal('target_longitude', 11, 8)->nullable()->after('target_latitude');

            // Basic telematics / maintenance linkage
            $table->decimal('odometer_miles', 10, 1)->nullable()->after('target_longitude');
            $table->string('eld_device_id')->nullable()->after('odometer_miles');
        });

        // Drivers: ELD / HOS state
        Schema::table('drivers', function (Blueprint $table) {
            // External reference to ELD / telematics system
            $table->string('eld_driver_id')->nullable()->after('geo_coordinates');

            // High‑level duty status to drive matching and compliance
            $table->enum('current_duty_status', [
                'off_duty',
                'sleeper',
                'driving',
                'on_duty_not_driving',
            ])->default('off_duty')->after('eld_driver_id');

            // Remaining hours in minutes to keep calculations simple
            $table->unsignedInteger('hos_drive_remaining_minutes')->nullable()->after('current_duty_status');
            $table->unsignedInteger('hos_on_duty_remaining_minutes')->nullable()->after('hos_drive_remaining_minutes');
            $table->unsignedInteger('hos_cycle_remaining_minutes')->nullable()->after('hos_on_duty_remaining_minutes');
        });

        // Shipments: richer load / map / external board data
        Schema::table('shipments', function (Blueprint $table) {
            // Load board and external system linkage
            $table->string('external_load_id')->nullable()->after('customer_id');
            $table->string('external_source')->nullable()->after('external_load_id');

            // Geocoded pickup / drop for mapping and distance calculations
            $table->decimal('pickup_latitude', 10, 8)->nullable()->after('pickup_address');
            $table->decimal('pickup_longitude', 11, 8)->nullable()->after('pickup_latitude');
            $table->decimal('drop_latitude', 10, 8)->nullable()->after('drop_address');
            $table->decimal('drop_longitude', 11, 8)->nullable()->after('drop_latitude');

            // Commercial / pricing data used by AI and reports
            $table->decimal('rate_total_usd', 10, 2)->nullable()->after('estimated_cost');
            $table->decimal('rate_per_mile_usd', 8, 4)->nullable()->after('rate_total_usd');
            $table->string('currency', 3)->default('USD')->after('rate_per_mile_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'current_latitude',
                'current_longitude',
                'current_speed_mph',
                'current_heading_deg',
                'last_location_update',
                'target_latitude',
                'target_longitude',
                'odometer_miles',
                'eld_device_id',
            ]);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'eld_driver_id',
                'current_duty_status',
                'hos_drive_remaining_minutes',
                'hos_on_duty_remaining_minutes',
                'hos_cycle_remaining_minutes',
            ]);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'external_load_id',
                'external_source',
                'pickup_latitude',
                'pickup_longitude',
                'drop_latitude',
                'drop_longitude',
                'rate_total_usd',
                'rate_per_mile_usd',
                'currency',
            ]);
        });
    }
};

