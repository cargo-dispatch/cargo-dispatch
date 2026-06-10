<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id')->nullable(); // null until driver created via invite
            $table->string('email')->index();
            $table->string('token', 64)->unique();
            $table->string('firstname')->nullable();   // pre-filled by admin
            $table->string('lastname')->nullable();
            $table->string('phoneno')->nullable();
            $table->unsignedBigInteger('driver_type_id')->nullable();
            $table->unsignedBigInteger('created_by');  // admin user_id
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
            $table->foreign('driver_type_id')->references('id')->on('driver_types')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_invitations');
    }
};
