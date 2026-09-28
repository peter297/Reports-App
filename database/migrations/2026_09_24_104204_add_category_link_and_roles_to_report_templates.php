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
        Schema::table('report_templates', function (Blueprint $table) {
            $table->string('category')->default('Report Template')->after('description');
            $table->string('link_url')->nullable()->after('file_path');
        });

        DB::statement('ALTER TABLE report_templates MODIFY file_path VARCHAR(255) NULL');
        DB::statement('ALTER TABLE report_templates MODIFY file_type VARCHAR(255) NULL');

        Schema::create('report_template_role', function (Blueprint $table) {
            $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['report_template_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_template_role');

        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropColumn(['category', 'link_url']);
        });
    }
};
