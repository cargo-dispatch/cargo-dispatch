<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('load_type', ['FTL', 'LTL'])->default('FTL')->after('equipment_required');
            $table->string('reference_number')->nullable()->after('load_type');
            $table->string('pickup_contact_name')->nullable()->after('reference_number');
            $table->string('pickup_contact_phone')->nullable()->after('pickup_contact_name');
            $table->string('delivery_contact_name')->nullable()->after('pickup_contact_phone');
            $table->string('delivery_contact_phone')->nullable()->after('delivery_contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'load_type',
                'reference_number',
                'pickup_contact_name',
                'pickup_contact_phone',
                'delivery_contact_name',
                'delivery_contact_phone',
            ]);
        });
    }
};
