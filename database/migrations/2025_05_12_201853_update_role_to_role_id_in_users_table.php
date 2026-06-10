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
       Schema::table('users', function (Blueprint $table) {
            // Remove old 'role' column
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            // Add new 'role_id' column
            $table->unsignedBigInteger('role_id')->nullable()->after('id');

            // Add foreign key constraint
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');

            // Restore original 'role' column if needed
            $table->string('role')->nullable();
        });
    }
};
