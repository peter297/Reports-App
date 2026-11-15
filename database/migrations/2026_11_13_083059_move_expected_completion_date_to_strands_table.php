<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syllabus_strands', function (Blueprint $table) {
            $table->date('expected_completion_date')->nullable()->after('description');
        });

        Schema::table('syllabus_lesson_progress', function (Blueprint $table) {
            $table->dropColumn('expected_completion_date');
        });
    }

    public function down(): void
    {
        Schema::table('syllabus_lesson_progress', function (Blueprint $table) {
            $table->date('expected_completion_date')->nullable();
            $table->index('expected_completion_date');
        });

        Schema::table('syllabus_strands', function (Blueprint $table) {
            $table->dropColumn('expected_completion_date');
        });
    }
};
