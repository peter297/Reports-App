<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- reports: extend the legacy inbox into the submission entity ---
        Schema::table('reports', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
            $table->string('subject')->nullable()->change();
            $table->text('summary')->nullable()->change();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('title')->nullable()->after('id');
            $table->foreignId('template_id')->nullable()->after('title')->constrained('report_templates')->nullOnDelete();
            $table->foreignId('template_version_id')->nullable()->after('template_id')->constrained('report_template_versions')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->after('template_version_id')->constrained('roles')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('role_id')->constrained('branches')->nullOnDelete();
            $table->foreignId('academic_level_id')->nullable()->after('branch_id')->constrained('academic_levels')->nullOnDelete();
            $table->date('period_start')->nullable()->after('academic_level_id');
            $table->date('period_end')->nullable()->after('period_start');
            $table->date('deadline')->nullable()->after('period_end');
            $table->string('frequency')->nullable()->after('deadline');
            $table->string('status')->default('draft')->after('frequency');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->unsignedInteger('current_revision')->default(1)->after('reviewed_at');
            $table->boolean('is_consolidated')->default(false)->after('current_revision');
            $table->string('compilation_kind')->nullable()->after('is_consolidated');
            $table->foreignId('compiled_by_user_id')->nullable()->after('compilation_kind')->constrained('users')->nullOnDelete();

            $table->index('status');
            $table->index(['period_start', 'period_end'], 'reports_period_idx');
            $table->index('branch_id');
        });

        // --- report_section_responses (snapshot of section content per report) ---
        Schema::create('report_section_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('report_template_sections')->nullOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_mandatory')->default(true);
            $table->string('data_source')->nullable();
            $table->longText('content')->nullable();
            $table->json('auto_payload')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'sort_order']);
        });

        // --- report_revisions (full snapshot each resubmission) ---
        Schema::create('report_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('snapshot');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['report_id', 'revision_number']);
        });

        // --- report_reviews (audit trail of review actions) ---
        Schema::create('report_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['report_id', 'created_at']);
        });

        // --- report_recipients (snapshot of who received a report) ---
        Schema::create('report_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['report_id', 'user_id']);
        });

        // --- report_compilations (1:1 lineage record for consolidated reports) ---
        Schema::create('report_compilations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->string('kind');
            $table->foreignId('compiler_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();

            $table->unique('report_id');
        });

        // --- report_compilation_sources (which reports fed a consolidation) ---
        Schema::create('report_compilation_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compilation_id')->constrained('report_compilations')->cascadeOnDelete();
            $table->foreignId('source_report_id')->constrained('reports')->cascadeOnDelete();
            $table->string('branch_label')->nullable();
            $table->json('contribution')->nullable();

            $table->unique(['compilation_id', 'source_report_id'], 'report_compilation_sources_unique');
        });

        // Legacy multi-recipient pivot, superseded by report_recipients.
        Schema::dropIfExists('report_user');
    }

    public function down(): void
    {
        Schema::dropIfExists('report_compilation_sources');
        Schema::dropIfExists('report_compilations');
        Schema::dropIfExists('report_recipients');
        Schema::dropIfExists('report_reviews');
        Schema::dropIfExists('report_revisions');
        Schema::dropIfExists('report_section_responses');

        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('compiled_by_user_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('template_version_id');
            $table->dropConstrainedForeignId('template_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('academic_level_id');
            $table->dropColumn([
                'title',
                'period_start',
                'period_end',
                'deadline',
                'frequency',
                'status',
                'submitted_at',
                'reviewed_at',
                'current_revision',
                'is_consolidated',
                'compilation_kind',
            ]);
        });

        Schema::create('report_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
