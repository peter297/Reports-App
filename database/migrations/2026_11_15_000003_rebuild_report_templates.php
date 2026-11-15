<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rebuild report_templates as the dynamic, role-based template entity.
        Schema::dropIfExists('report_templates');

        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('frequency')->default('monthly');
            $table->boolean('is_active')->default(true);
            // Added as a real FK after report_template_versions exists (see below).
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('report_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('change_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['report_template_id', 'version']);
        });

        Schema::create('report_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_version_id')->constrained('report_template_versions')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_auto')->default(false);
            $table->string('data_source')->nullable();
            $table->text('help_text')->nullable();
            $table->timestamps();

            $table->index(['report_template_version_id', 'sort_order'], 'report_template_sections_order_idx');
        });

        Schema::table('report_templates', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('report_template_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_template_sections');
        Schema::dropIfExists('report_template_versions');
        Schema::dropIfExists('report_templates');

        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }
};
