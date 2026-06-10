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
        Schema::create('vehicles', function (Blueprint $table) {
              $table->id();
    $table->string('vehicle_id')->nullable(); 
    $table->string('license_plate_number');
    $table->string('vin')->unique(); 
    $table->string('make_model');
    $table->year('year_of_manufacture');
    $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('cascade'); 
    $table->string('color')->nullable();
    $table->enum('ownership_status', ['Owned', 'Leased', 'Rented']);
    $table->decimal('cargo_weight', 8, 2)->nullable(); 
    $table->decimal('cargo_volume', 8, 2)->nullable(); 
    $table->json('load_type_compatibility')->nullable(); 
    $table->date('registration_expiry_date')->nullable();
    $table->text('insurance_details')->nullable();
    $table->date('insurance_expiry_date')->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
