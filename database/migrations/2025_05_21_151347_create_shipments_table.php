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
        Schema::create('shipments', function (Blueprint $table) {
          $table->id();
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->foreignId('vehicle_type_id')->constrained()->onDelete('cascade');
    $table->decimal('weight', 8, 2)->nullable();
    $table->decimal('volume', 8, 2)->nullable();
    $table->integer('pallets')->nullable();
    $table->text('pickup_address')->nullable();
    $table->text('drop_address')->nullable();
    $table->dateTime('pickup_time')->nullable();
    $table->dateTime('delivery_time')->nullable();
    $table->text('special_instructions')->nullable();
    $table->decimal('estimated_cost', 10, 2)->nullable();
    $table->json('equipment_required')->nullable(); // Store as JSON
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
