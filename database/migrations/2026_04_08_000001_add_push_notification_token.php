<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('expo_push_token')->nullable()->after('connectycube_id');
            $table->timestamp('last_push_token_update')->nullable()->after('expo_push_token');
            $table->index('expo_push_token');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['expo_push_token']);
            $table->dropColumn(['expo_push_token', 'last_push_token_update']);
        });
    }
};
