<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('branch')->nullable()->after('id');
            $table->string('section')->nullable()->after('branch');
            $table->date('attendance_date')->nullable()->after('week_id');
            $table->unsignedInteger('class_total')->default(0)->after('total_girls');
            $table->unsignedInteger('total_present')->default(0)->after('class_total');
            $table->unsignedInteger('total_absent')->default(0)->after('total_present');
            $table->decimal('percentage_present', 5, 2)->default(0)->after('total_absent');
            $table->decimal('percentage_absent', 5, 2)->default(0)->after('percentage_present');
            $table->index(['branch', 'section', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['branch', 'section', 'attendance_date']);
            $table->dropColumn([
                'branch',
                'section',
                'attendance_date',
                'class_total',
                'total_present',
                'total_absent',
                'percentage_present',
                'percentage_absent',
            ]);
        });
    }
};
