<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_assignments', function (Blueprint $table) {
            // The old index doubles as the class/user FK support; add the widened one first.
            $table->unique(['year_session_id', 'subject_id', 'class_id', 'stream_id', 'user_id', 'assignment_role'], 'subject_assignments_unique_v2');
        });

        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->dropUnique('subject_assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->unique(['year_session_id', 'subject_id', 'class_id', 'user_id', 'assignment_role'], 'subject_assignments_unique');
        });

        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->dropUnique('subject_assignments_unique_v2');
        });
    }
};
