<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('branch')->nullable();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_lessons')->default(0);
            $table->string('status')->default('draft'); // draft | active | archived
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['year_session_id', 'term_id', 'class_id', 'subject_id', 'branch'], 'syllabus_mappings_lookup');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_mappings');
    }
};
