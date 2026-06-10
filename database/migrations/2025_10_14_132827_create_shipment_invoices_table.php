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
        Schema::create('shipment_invoices', function (Blueprint $table) {
            $table->id();
             $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
           $table->decimal('miles_per_gallon', 10, 2);
            $table->decimal('fuel_cost', 10, 2);
            $table->decimal('fuel_price', 10, 2);
            $table->decimal('driver_pay', 10, 2);
            $table->decimal('driver_cost', 10, 2);
            $table->decimal('tolls_fee', 10, 2);
            $table->decimal('profit_percentage', 10, 2);
           $table->decimal('extra_charges', 10, 2)->default(0)->nullable();
           $table->longText('invoice_note')->nullable();
            $table->decimal('total_cost', 10, 2);
            $table->decimal('toatl_with_profit', 10, 2);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_invoices');
    }
};
