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
        Schema::create('cms', function (Blueprint $table) {
             $table->id();
            $table->enum('type', ['CMS', 'Services'])->default('CMS');
            $table->string('title', 500);
            $table->text('slug');
            $table->text('meta_tags')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('image')->nullable();
            $table->longText('content');
            $table->boolean('is_active')->default(true);
       
            $table->index('type');
            $table->index('slug');
            $table->index('is_active');
                 $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms');
    }
};
