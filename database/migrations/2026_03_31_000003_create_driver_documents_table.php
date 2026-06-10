<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');

            $table->enum('type', [
                'profile_photo',
                'cdl_front',
                'cdl_back',
                'medical_card',
                'drug_test',
                'mvr_report',
                'proof_of_insurance',
                'w9_form',
                'direct_deposit',
                'other',
            ]);

            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedInteger('file_size')->nullable();   // bytes
            $table->string('mime_type')->nullable();

            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->unsignedBigInteger('verified_by')->nullable();   // admin user_id
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('expires_at')->nullable();   // CDL, medical card have expiry

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('driver_id')->references('id')->on('drivers')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_documents');
    }
};
