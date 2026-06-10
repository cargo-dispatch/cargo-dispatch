<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixExtractionStatusColumnLength extends Migration
{
    public function up()
    {
        Schema::table('shipment_documents', function (Blueprint $table) {
            // Change column to have longer length (ENUM or VARCHAR)
            $table->string('extraction_status', 50)->default('pending')->change();
        });
    }

    public function down()
    {
        Schema::table('shipment_documents', function (Blueprint $table) {
            $table->string('extraction_status', 20)->change();
        });
    }
}