<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('section');
            $table->string('branch')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('academic_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('branch');
            $table->string('section');
            $table->foreignId('year_session_id')->constrained('year_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('exam_type');
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete();
            $table->json('subjects')->nullable();
            $table->timestamps();

            $table->unique(
                ['branch', 'section', 'year_session_id', 'term_id', 'exam_type', 'class_id', 'stream_id'],
                'academic_analyses_scope_unique'
            );
        });

        $eyeSubjects = [
            'Mathematics',
            'English',
            'Kiswahili',
            'Islamic Religious Education',
            'Environmental Activities',
            'Creative Activities',
        ];

        foreach ($eyeSubjects as $index => $name) {
            DB::table('subjects')->insert([
                'name' => $name,
                'section' => 'EYE',
                'branch' => null,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_analyses');
        Schema::dropIfExists('subjects');
    }
};
