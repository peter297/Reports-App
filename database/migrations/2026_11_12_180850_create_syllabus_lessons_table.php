<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syllabus_substrand_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('lesson_number');
            $table->string('topic');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['syllabus_substrand_id', 'sort_order']);
            $table->index('lesson_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_lessons');
    }
};
