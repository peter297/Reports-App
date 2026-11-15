<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_assessment_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syllabus_assessment_id')->constrained('syllabus_assessments')->cascadeOnDelete();
            $table->foreignId('syllabus_lesson_id')->constrained('syllabus_lessons')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['syllabus_assessment_id', 'syllabus_lesson_id'], 'syllabus_assessment_lessons_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_assessment_lessons');
    }
};
