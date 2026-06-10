<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->decimal('ifta_per_mile', 8, 4)->nullable()->default(0.05)->after('overhead_per_mile');
        });

        Schema::table('shipment_invoices', function (Blueprint $table) {
            $table->decimal('ifta_per_mile', 8, 4)->nullable()->default(0)->after('overhead_cost');
            $table->decimal('ifta_cost', 10, 2)->nullable()->default(0)->after('ifta_per_mile');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropColumn('ifta_per_mile');
        });

        Schema::table('shipment_invoices', function (Blueprint $table) {
            $table->dropColumn(['ifta_per_mile', 'ifta_cost']);
        });
    }
};
