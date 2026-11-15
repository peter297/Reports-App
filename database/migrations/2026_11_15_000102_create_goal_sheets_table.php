<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_sheet_category_id')->constrained('goal_sheet_categories')->restrictOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('staff_name');
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->unsignedSmallInteger('goal_year')->index();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete(); // Line Manager 1 (Coordinator)
            $table->string('coordinator_name')->nullable(); // snapshot for workbook import
            $table->foreignId('line_manager_2_id')->nullable()->constrained('users')->nullOnDelete(); // Line Manager 2 (Deputy)
            $table->string('line_manager_2_name')->nullable(); // snapshot for workbook import
            $table->string('status')->default('draft')->index(); // draft | submitted | acknowledged
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('end_of_year_rating', 5, 2)->nullable(); // "To be filled at END OF YEAR" slot from the workbook
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['goal_sheet_category_id', 'goal_year']);
            $table->index('staff_user_id');
        });

        Schema::create('goal_sheet_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_sheet_id')->constrained('goal_sheets')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->foreignId('goal_category_id')->nullable()->constrained('goal_categories')->nullOnDelete();
            $table->string('category_name'); // snapshot so history survives category deletion
            $table->unsignedSmallInteger('goal_number')->nullable();
            $table->text('goal_statement')->nullable();
            $table->decimal('goal_for_year', 6, 2)->nullable(); // percent
            $table->string('uom', 20)->default('%');
            $table->text('calculation_method')->nullable();
            $table->string('source_of_data')->nullable();
            $table->string('achievement_type', 2)->default('HB'); // HB | LB
            $table->decimal('performance_weight', 6, 4)->nullable();
            $table->timestamps();

            $table->index('goal_sheet_id');
        });

        Schema::create('goal_sheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_sheet_goal_id')->constrained('goal_sheet_goals')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('subject_name')->nullable();
            $table->string('class_name')->nullable();
            // Termly targets (percent)
            $table->decimal('term_1', 6, 2)->nullable();
            $table->decimal('term_2', 6, 2)->nullable();
            $table->decimal('term_3', 6, 2)->nullable();
            // Monthly performance checkpoints (percent)
            $table->decimal('op_t1', 6, 2)->nullable();
            $table->decimal('mid_t1', 6, 2)->nullable();
            $table->decimal('end_t1', 6, 2)->nullable();
            $table->decimal('mid_t2', 6, 2)->nullable();
            $table->decimal('end_t2', 6, 2)->nullable();
            $table->decimal('mid_t3', 6, 2)->nullable();
            $table->decimal('end_t3', 6, 2)->nullable();
            $table->timestamps();

            $table->index('goal_sheet_goal_id');
        });

        Schema::create('goal_sheet_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_sheet_id')->constrained('goal_sheets')->cascadeOnDelete();
            $table->string('party', 20); // owner | line_manager_1 | line_manager_2
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name')->nullable();
            $table->boolean('signed')->default(false);
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['goal_sheet_id', 'party']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_sheet_acknowledgements');
        Schema::dropIfExists('goal_sheet_entries');
        Schema::dropIfExists('goal_sheet_goals');
        Schema::dropIfExists('goal_sheets');
    }
};
