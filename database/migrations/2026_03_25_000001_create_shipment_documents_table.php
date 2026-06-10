<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->onDelete('cascade');

            $table->foreignId('driver_id')
                ->nullable()
                ->constrained('drivers')
                ->nullOnDelete();

            $table->enum('document_type', ['BOL', 'POD', 'RATE_CONFIRMATION', 'OTHER']);

            // Stored file path relative to the storage disk (e.g. public)
            $table->string('file_path');

            $table->json('extracted_fields')->nullable();
            $table->enum('extraction_status', ['pending', 'extracted', 'failed'])
                ->default('pending');
            $table->decimal('extraction_confidence', 5, 2)->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_documents');
    }
};

