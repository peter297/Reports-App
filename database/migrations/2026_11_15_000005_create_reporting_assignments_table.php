<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Explicit reporting lines (who reports to whom), many-to-many so
        // e.g. an HR Officer can report to both the Principal and the CEO.
        Schema::create('reporting_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('direct'); // direct | secondary
            $table->timestamps();

            $table->unique(['user_id', 'manager_user_id']);
            $table->index('manager_user_id');
        });

        // Scope-based routing rules (role + optional branch/level -> recipient).
        // Resolved dynamically at submission time and snapshotted into
        // report_recipients; edits never rewrite historical reports.
        Schema::create('reporting_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitter_role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('academic_level_id')->nullable()->constrained('academic_levels')->nullOnDelete();
            $table->foreignId('recipient_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'submitter_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_assignments');
        Schema::dropIfExists('reporting_relationships');
    }
};
