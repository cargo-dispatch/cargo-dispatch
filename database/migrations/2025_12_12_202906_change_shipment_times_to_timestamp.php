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
        $table->timestamp('pickup_time')->nullable()->change();
        $table->timestamp('delivery_time')->nullable()->change();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('shipments', function (Blueprint $table) {
        $table->dateTime('pickup_time')->nullable()->change();
        $table->dateTime('delivery_time')->nullable()->change();
    });
    }
};
