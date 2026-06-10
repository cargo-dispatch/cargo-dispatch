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
       Schema::table('customers', function (Blueprint $table) {
            // Rename 'address' to 'address1'
            $table->renameColumn('address', 'address1');

            // Add new columns
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('customer_title')->nullable();
            $table->string('user_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('customers', function (Blueprint $table) {
            // Revert column name
            $table->renameColumn('address1', 'address');

            // Drop added columns
            $table->dropColumn(['address2', 'city', 'state', 'zip', 'customer_title', 'user_name']);
        });
    }
};
