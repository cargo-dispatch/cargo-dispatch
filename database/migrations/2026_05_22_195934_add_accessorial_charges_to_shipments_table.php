<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('deadhead_miles', 8, 2)->nullable()->after('delivery_contact_phone');
            $table->decimal('detention_hours', 8, 2)->nullable()->after('deadhead_miles');
            $table->decimal('lumper_fee', 10, 2)->nullable()->after('detention_hours');
            $table->integer('per_diem_days')->nullable()->after('lumper_fee');
            $table->decimal('scale_fees', 10, 2)->nullable()->after('per_diem_days');
            $table->boolean('tarp_required')->default(false)->after('scale_fees');
            $table->decimal('permit_fee', 10, 2)->nullable()->after('tarp_required');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'deadhead_miles', 'detention_hours', 'lumper_fee',
                'per_diem_days', 'scale_fees', 'tarp_required', 'permit_fee',
            ]);
        });
    }
};
