<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('ends_at');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
            $table->timestamp('contacted_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['notes', 'contacted_at']);
        });
    }
};
