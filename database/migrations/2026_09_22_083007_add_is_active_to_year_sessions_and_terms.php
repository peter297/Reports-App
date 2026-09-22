<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('year_sessions', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('name');
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('name');
        });

        if (! DB::table('year_sessions')->where('is_active', true)->exists()) {
            DB::table('year_sessions')->orderByDesc('id')->limit(1)->update(['is_active' => true]);
        }

        $activeSessionId = DB::table('year_sessions')->where('is_active', true)->value('id');

        if ($activeSessionId && ! DB::table('terms')->where('is_active', true)->exists()) {
            DB::table('terms')
                ->where('year_session_id', $activeSessionId)
                ->orderByDesc('id')->limit(1)
                ->update(['is_active' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('year_sessions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
