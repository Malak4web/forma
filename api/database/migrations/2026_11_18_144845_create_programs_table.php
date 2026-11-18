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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle');
            $table->text('pitch');
            $table->unsignedSmallInteger('days');
            $table->string('minutes', 16);           // e.g. "15-20"
            $table->string('level', 64);
            $table->string('thumb');
            $table->string('tag')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('program_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained();
            $table->unsignedTinyInteger('number');
            $table->string('title');
            $table->json('pool');                    // exercise slugs rotated through the week
            $table->timestamps();
            $table->unique(['program_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_weeks');
        Schema::dropIfExists('programs');
    }
};
