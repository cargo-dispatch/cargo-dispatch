<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL: Change the ENUM type for status column
        DB::statement("ALTER TABLE shipments MODIFY status ENUM('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled', 'active', 'complete') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE shipments MODIFY status ENUM('active', 'pending', 'complete') DEFAULT 'pending'");
    }
};
