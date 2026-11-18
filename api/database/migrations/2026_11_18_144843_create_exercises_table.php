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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');                 // English exercise name (Squat, Plank...)
            $table->string('hook');                  // Arabic one-liner from the reel
            $table->string('category', 32)->index();
            $table->unsignedSmallInteger('seconds');
            $table->string('level', 32)->nullable();
            $table->string('muscles')->nullable();
            $table->json('tips')->nullable();
            $table->string('thumb');
            $table->string('video_url')->nullable();
            $table->boolean('is_home')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
