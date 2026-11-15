<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return collect(Schema::getForeignKeys($table))->where('name', $constraint)->isNotEmpty();
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->where('name', $name)->isNotEmpty();
    }

    public function up(): void
    {
        // --- Syllabus mappings get a stream reference ---
        if (! Schema::hasColumn('syllabus_mappings', 'stream_id')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->foreignId('stream_id')->nullable()->after('class_id')->constrained('streams')->nullOnDelete();
                $table->index(['stream_id']);
            });
        }

        // Backfill stream_id: if a class has exactly one stream, attach it to mappings of that class.
        $classStreamCounts = DB::table('streams')->selectRaw('class_id, COUNT(*) as c')->groupBy('class_id')->pluck('c', 'class_id');

        foreach (DB::table('syllabus_mappings')->whereNull('stream_id')->get(['id', 'class_id']) as $mapping) {
            if (($classStreamCounts[$mapping->class_id] ?? 0) === 1) {
                $streamId = DB::table('streams')->where('class_id', $mapping->class_id)->value('id');
                DB::table('syllabus_mappings')->where('id', $mapping->id)->update(['stream_id' => $streamId]);
            }
        }

        // --- Subject assignments become per school session ---
        if (! Schema::hasColumn('subject_assignments', 'year_session_id')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->foreignId('year_session_id')->nullable()->after('id')->constrained('year_sessions')->cascadeOnDelete();
            });
        }

        $activeSessionId = DB::table('year_sessions')->where('status', 'active')->orderByDesc('id')->value('id')
            ?? DB::table('year_sessions')->orderByDesc('id')->value('id');

        if ($activeSessionId) {
            DB::table('subject_assignments')->whereNull('year_session_id')->update(['year_session_id' => $activeSessionId]);
        }

        if (! $this->indexExists('subject_assignments', 'subject_assignments_fk_lookup')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->index(['subject_id', 'class_id', 'user_id'], 'subject_assignments_fk_lookup');
            });
        }

        if ($this->indexExists('subject_assignments', 'subject_assignments_unique')) {
            Schema::table('subject_assignments', function (Blueprint $table) {
                $table->dropUnique('subject_assignments_unique');
            });
        }

        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->foreignId('year_session_id')->nullable(false)->change();
            $table->unique(['year_session_id', 'subject_id', 'class_id', 'user_id', 'assignment_role'], 'subject_assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->dropUnique('subject_assignments_unique');
            $table->unique(['subject_id', 'class_id', 'user_id', 'assignment_role'], 'subject_assignments_unique');
            $table->dropIndex('subject_assignments_fk_lookup');
            $table->dropConstrainedForeignId('year_session_id');
        });

        Schema::table('syllabus_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('syllabus_mappings', 'stream_id')) {
                $table->dropForeign(['stream_id']);
                $table->dropIndex(['stream_id']);
                $table->dropColumn('stream_id');
            }
        });
    }
};
