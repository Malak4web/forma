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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->index();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('goal', 16)->nullable();
            $table->char('sex', 1)->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->decimal('weight', 5, 1)->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->decimal('activity', 4, 3)->nullable();
            $table->unsignedSmallInteger('kcal')->nullable();
            $table->unsignedSmallInteger('protein')->nullable();
            $table->unsignedSmallInteger('carbs')->nullable();
            $table->unsignedSmallInteger('fat')->nullable();
            $table->string('source', 32)->default('calculator');
            $table->string('status', 16)->default('new')->index(); // new | contacted | converted
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
