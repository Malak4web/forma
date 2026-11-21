<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Logged-in member experience: saved nutrition plan, daily log, coach chat, member check-ins. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('nutrition')->nullable()->after('goal');    // {input, kcal, protein, carbs, fat}
            $table->date('nutrition_since')->nullable()->after('nutrition');
        });

        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->date('date');
            $table->unsignedTinyInteger('water')->default(0); // cups of ~250ml
            $table->json('meals')->nullable();                 // indexes of meals eaten
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'date']);
        });

        Schema::create('coach_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();                    // the member (thread)
            $table->foreignId('admin_id')->nullable()->constrained('users'); // coach who replied
            $table->string('sender', 8);                                     // member | coach
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->string('source', 8)->default('coach')->after('status')->index(); // coach | member
        });
    }

    public function down(): void
    {
        Schema::table('follow_ups', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::dropIfExists('coach_messages');
        Schema::dropIfExists('daily_logs');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['nutrition', 'nutrition_since']));
    }
};
