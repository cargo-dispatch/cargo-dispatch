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
        Schema::table('drivers', function (Blueprint $table) {
            // Add ConnectyCube integration fields if they don't exist
            if (!Schema::hasColumn('drivers', 'connectycube_id')) {
                $table->bigInteger('connectycube_id')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('drivers', 'connectycube_login')) {
                $table->string('connectycube_login')->nullable()->after('connectycube_id');
            }
            
            if (!Schema::hasColumn('drivers', 'connectycube_password')) {
                $table->string('connectycube_password')->nullable()->after('connectycube_login');
            }
            
            // Add remember token for authentication if not exists
            if (!Schema::hasColumn('drivers', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            
            // Add index for better performance
            $table->index('connectycube_id');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['connectycube_id']);
            $table->dropColumn(['connectycube_id', 'connectycube_login', 'connectycube_password', 'remember_token']);
        });
    }
};
