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
        Schema::create('driver_credentials', function (Blueprint $table) {
             $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->string('title');
            $table->date('expiry_date')->nullable();
            $table->string('file'); // store file path
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('driver_credentials');
    }
};
