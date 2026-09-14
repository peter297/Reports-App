<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->string('branch')->nullable()->after('id');
            $table->unsignedInteger('total_learners')->after('term_id');
            $table->json('class_breakdown')->nullable()->after('total_learners');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropColumn(['branch', 'total_learners', 'class_breakdown']);
        });
    }
};
