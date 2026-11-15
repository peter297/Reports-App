<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('academic_level_id')->constrained('academic_levels')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'branch_id', 'academic_level_id'], 'coordinator_assignments_unique');
            $table->index(['branch_id', 'academic_level_id'], 'coordinator_assignments_scope_idx');
        });

        // The legacy table held no rows and used unlinked string branch/section
        // values; its responsibility is fully covered by coordinator_assignments.
        Schema::dropIfExists('section_coordinator_assignments');
    }

    public function down(): void
    {
        Schema::create('section_coordinator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('branch');
            $table->string('section');
            $table->timestamps();

            $table->unique(['user_id', 'branch', 'section']);
        });

        Schema::dropIfExists('coordinator_assignments');
    }
};
