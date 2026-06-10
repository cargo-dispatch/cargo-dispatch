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
        Schema::create('drivers', function (Blueprint $table) {
             $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('phoneno');
            $table->string('emergencycontactno');
            $table->string('email')->unique();
$table->foreign('drivertype')->references('id')->on('driver_types')->onDelete('cascade');
            $table->string('licensetype');
            $table->string('licenseno');
            $table->timestamps();

            // Set up the foreign key constraint
            $table->foreign('drivertype')->references('id')->on('drivers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
