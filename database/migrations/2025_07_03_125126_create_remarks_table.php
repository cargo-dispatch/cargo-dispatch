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
        Schema::create('remarks', function (Blueprint $table) {
          $table->id();
$table->unsignedBigInteger('shipment_id');         // Shipment reference
$table->unsignedBigInteger('commenter_id');        // ID of who made the comment
$table->string('commenter_type');                  // Model type: Driver / Customer / User
$table->string('comments');
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remarks');
    }
};
