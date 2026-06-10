<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_invoices', function (Blueprint $table) {
            $table->decimal('insurance_per_mile', 8, 4)->nullable()->default(0)->after('driver_cost');
            $table->decimal('insurance_cost', 10, 2)->nullable()->default(0)->after('insurance_per_mile');
            $table->decimal('maintenance_per_mile', 8, 4)->nullable()->default(0)->after('insurance_cost');
            $table->decimal('maintenance_cost', 10, 2)->nullable()->default(0)->after('maintenance_per_mile');
            $table->decimal('overhead_per_mile', 8, 4)->nullable()->default(0)->after('maintenance_cost');
            $table->decimal('overhead_cost', 10, 2)->nullable()->default(0)->after('overhead_per_mile');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'insurance_per_mile', 'insurance_cost',
                'maintenance_per_mile', 'maintenance_cost',
                'overhead_per_mile', 'overhead_cost',
            ]);
        });
    }
};
