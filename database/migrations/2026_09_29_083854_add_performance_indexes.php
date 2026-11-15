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
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['branch', 'section', 'attendance_date'], 'attendances_branch_section_date_index');
            $table->index(['year_session_id', 'term_id', 'week_id'], 'attendances_session_term_week_index');
        });

        Schema::table('academic_analyses', function (Blueprint $table) {
            $table->index(['year_session_id', 'term_id'], 'academic_analyses_session_term_index');
            $table->index(['branch', 'section'], 'academic_analyses_branch_section_index');
        });

        Schema::table('class_sizes', function (Blueprint $table) {
            $table->index(['year_session_id', 'term_id'], 'class_sizes_session_term_index');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['branch'], 'enrollments_branch_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index(['event_date'], 'events_event_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_event_date_index');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_branch_index');
        });

        Schema::table('class_sizes', function (Blueprint $table) {
            $table->dropIndex('class_sizes_session_term_index');
        });

        Schema::table('academic_analyses', function (Blueprint $table) {
            $table->dropIndex('academic_analyses_branch_section_index');
            $table->dropIndex('academic_analyses_session_term_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_session_term_week_index');
            $table->dropIndex('attendances_branch_section_date_index');
        });
    }
};
