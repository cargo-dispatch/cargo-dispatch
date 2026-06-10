<?php

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
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
        Schema::create('shipment_associated_drivers', function (Blueprint $table) {
            $table->id();
           $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
           $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_associated_drivers');
    }
};
