<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('admin_id')->nullable()->constrained('users');
            $table->date('followup_date')->index();
            $table->date('next_followup_date')->nullable()->index();
            $table->string('status', 24)->default('on_track')->index(); // on_track | needs_attention | delayed | completed

            // Body measurements
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('waist_cm', 5, 1)->nullable();
            $table->decimal('chest_cm', 5, 1)->nullable();
            $table->decimal('arm_cm', 5, 1)->nullable();
            $table->decimal('thigh_cm', 5, 1)->nullable();
            $table->decimal('hips_cm', 5, 1)->nullable();
            $table->decimal('body_fat', 4, 1)->nullable();

            // Adherence & wellbeing
            $table->unsignedTinyInteger('diet_adherence')->nullable();    // 1..10
            $table->unsignedTinyInteger('workout_adherence')->nullable(); // 1..10
            $table->decimal('sleep_hours', 3, 1)->nullable();
            $table->decimal('water_liters', 3, 1)->nullable();
            $table->unsignedTinyInteger('energy_level')->nullable();      // 1..5

            // Coaching details
            $table->text('member_feedback')->nullable();
            $table->text('coach_notes')->nullable();
            $table->text('plan_adjustments')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
