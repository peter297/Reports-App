<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syllabus_mapping_id')->constrained()->cascadeOnDelete();
            $table->date('assessment_date');
            $table->string('assessment_type')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['syllabus_mapping_id', 'assessment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_assessments');
    }
};
