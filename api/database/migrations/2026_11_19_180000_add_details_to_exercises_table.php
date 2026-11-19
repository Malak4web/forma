<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->text('description')->nullable()->after('tips');
            $table->unsignedTinyInteger('sets')->nullable()->default(3)->after('seconds');
            $table->unsignedSmallInteger('work_seconds')->nullable()->default(40)->after('sets');
            $table->unsignedSmallInteger('rest_seconds')->nullable()->default(20)->after('work_seconds');
            $table->string('reps', 32)->nullable()->after('rest_seconds');
            $table->string('equipment', 80)->nullable()->after('muscles');
            $table->unsignedSmallInteger('calories_est')->nullable()->after('equipment');
            $table->boolean('is_active')->default(true)->index()->after('is_home');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'sets', 'work_seconds', 'rest_seconds',
                'reps', 'equipment', 'calories_est', 'is_active',
            ]);
        });
    }
};
