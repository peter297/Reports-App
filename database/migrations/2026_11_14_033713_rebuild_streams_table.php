<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->unique(['class_id', 'name'], 'streams_class_name_unique');
        });

        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn(['branch', 'section']);
        });
    }

    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->string('branch')->nullable();
            $table->string('section')->nullable();
        });

        $classBranches = \Illuminate\Support\Facades\DB::table('classes')
            ->join('branches', 'branches.id', '=', 'classes.branch_id')
            ->join('academic_levels', 'academic_levels.id', '=', 'classes.academic_level_id')
            ->get(['classes.id', 'branches.name as branch', 'academic_levels.name as section']);

        foreach ($classBranches as $row) {
            \Illuminate\Support\Facades\DB::table('streams')->where('class_id', $row->id)->update([
                'branch' => $row->branch,
                'section' => $row->section,
            ]);
        }

        Schema::table('streams', function (Blueprint $table) {
            $table->dropUnique('streams_class_name_unique');
            $table->dropColumn('is_active');
        });
    }
};
