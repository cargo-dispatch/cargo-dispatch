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
        Schema::table('drivers', function (Blueprint $table) {
             $table->dropForeign(['drivertype']);

        // Now add the correct one
        $table->foreign('drivertype')->references('id')->on('driver_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropForeign(['drivertype']);

        // Restore the old (incorrect) foreign key, if needed
        $table->foreign('drivertype')->references('id')->on('drivers')->onDelete('cascade');
        });
    }
};
