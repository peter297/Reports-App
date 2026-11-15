<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syllabus_lesson_id')->constrained('syllabus_lessons')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('expected_completion_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamps();
            $table->unique(['syllabus_lesson_id', 'teacher_id']);
            $table->index('teacher_id');
            $table->index('completion_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_lesson_progress');
    }
};
