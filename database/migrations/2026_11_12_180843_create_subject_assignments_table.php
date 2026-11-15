<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assignment_role'); // teacher | coordinator
            $table->string('branch')->nullable();
            $table->timestamps();
            $table->unique(['subject_id', 'class_id', 'user_id', 'assignment_role'], 'subject_assignments_unique');
            $table->index(['user_id', 'assignment_role']);
            $table->index(['class_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_assignments');
    }
};
