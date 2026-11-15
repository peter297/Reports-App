<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return collect(Schema::getForeignKeys($table))->where('name', $constraint)->isNotEmpty();
    }

    public function up(): void
    {
        if (! Schema::hasColumn('subjects', 'academic_level_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('academic_level_id')->nullable()->after('code')->constrained('academic_levels')->nullOnDelete();
            });
        }

        $levels = DB::table('academic_levels')->pluck('id', 'name');

        foreach (DB::table('subjects')->whereNotNull('section')->get(['id', 'section']) as $subject) {
            $levelId = $levels[$subject->section] ?? null;

            if ($levelId) {
                DB::table('subjects')->where('id', $subject->id)->update(['academic_level_id' => $levelId]);
            }
        }

        if ($this->foreignKeyExists('subjects', 'subjects_academic_level_id_foreign')) {
            Schema::table('subjects', fn (Blueprint $table) => $table->dropForeign(['academic_level_id']));
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('academic_level_id')->nullable(false)->change();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('academic_level_id')->references('id')->on('academic_levels')->restrictOnDelete();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_name_section_unique');
            $table->dropIndex('subjects_section_index');
            $table->dropColumn('section');
            $table->unique(['name', 'academic_level_id'], 'subjects_name_level_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('section')->nullable();
        });

        $levels = DB::table('academic_levels')->pluck('name', 'id');

        foreach (DB::table('subjects')->get(['id', 'academic_level_id']) as $subject) {
            DB::table('subjects')->where('id', $subject->id)->update(['section' => $levels[$subject->academic_level_id] ?? null]);
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_name_level_unique');
            $table->dropConstrainedForeignId('academic_level_id');
            $table->string('academic_level_id')->nullable()->change();
            $table->unique(['name', 'section'], 'subjects_name_section_unique');
            $table->index('section');
        });
    }
};
