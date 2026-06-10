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
                       $table->string('connectycube_id')->nullable()->after('role_id');
            $table->string('connectycube_login')->nullable()->after('connectycube_id');
            $table->string('connectycube_password')->nullable()->after('connectycube_login');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
              $table->dropColumn(['connectycube_id', 'connectycube_login', 'connectycube_password']);
        });
    }
};
