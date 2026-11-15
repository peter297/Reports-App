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
        Schema::table('class_sizes', function (Blueprint $table) {
            $table->foreignId('year_session_id')->nullable()->after('id')->constrained('year_sessions')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->after('year_session_id')->constrained('terms')->nullOnDelete();
        });

        $sessionId = DB::table('year_sessions')->where('is_active', true)->orderByDesc('id')->value('id')
            ?? DB::table('year_sessions')->orderByDesc('id')->value('id');

        $termId = DB::table('terms')->where('is_active', true)->orderByDesc('id')->value('id')
            ?? DB::table('terms')->orderByDesc('id')->value('id');

        DB::table('class_sizes')
            ->whereNull('year_session_id')
            ->update(['year_session_id' => $sessionId]);

        DB::table('class_sizes')
            ->whereNull('term_id')
            ->update(['term_id' => $termId]);

        Schema::table('class_sizes', function (Blueprint $table) {
            $table->dropUnique('class_sizes_scope_unique');
            $table->unique(
                ['year_session_id', 'term_id', 'branch', 'section', 'class_id', 'stream_id'],
                'class_sizes_scope_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sizes', function (Blueprint $table) {
            $table->dropUnique('class_sizes_scope_unique');
            $table->unique(
                ['branch', 'section', 'class_id', 'stream_id'],
                'class_sizes_scope_unique'
            );
            $table->dropConstrainedForeignId('year_session_id');
            $table->dropConstrainedForeignId('term_id');
        });
    }
};
