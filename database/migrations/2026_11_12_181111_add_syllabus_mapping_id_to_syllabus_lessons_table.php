<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syllabus_lessons', function (Blueprint $table) {
            $table->foreignId('syllabus_mapping_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('syllabus_lessons', function (Blueprint $table) {
            $table->index(['syllabus_mapping_id', 'lesson_number']);
        });

        // Backfill for any rows created before this column existed (table is new, so typically no-op).
        \Illuminate\Support\Facades\DB::statement('
            UPDATE syllabus_lessons
            SET syllabus_mapping_id = (
                SELECT syllabus_strands.syllabus_mapping_id
                FROM syllabus_substrands
                INNER JOIN syllabus_strands ON syllabus_strands.id = syllabus_substrands.syllabus_strand_id
                WHERE syllabus_substrands.id = syllabus_lessons.syllabus_substrand_id
            )
            WHERE syllabus_mapping_id IS NULL
        ');

        Schema::table('syllabus_lessons', function (Blueprint $table) {
            $table->foreignId('syllabus_mapping_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('syllabus_lessons', function (Blueprint $table) {
            $table->dropForeign(['syllabus_mapping_id']);
            $table->dropIndex(['syllabus_mapping_id', 'lesson_number']);
            $table->dropColumn('syllabus_mapping_id');
        });
    }
};
