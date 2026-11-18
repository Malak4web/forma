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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('price');        // EGP, whole pounds
            $table->string('period', 16);            // free | once | monthly
            $table->unsignedSmallInteger('duration_days')->nullable(); // null = lifetime
            $table->json('features');
            $table->string('cta');
            $table->string('tag')->nullable();
            $table->boolean('highlight')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('plan_program', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('program_id')->constrained();
            $table->primary(['plan_id', 'program_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_program');
        Schema::dropIfExists('plans');
    }
};
