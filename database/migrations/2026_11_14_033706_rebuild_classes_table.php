<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->where('name', $constraint)
            ->isNotEmpty();
    }

    public function up(): void
    {
        if (! Schema::hasColumn('classes', 'branch_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
                $table->foreignId('academic_level_id')->nullable()->after('branch_id')->constrained('academic_levels')->nullOnDelete();
                $table->boolean('is_active')->default(true)->after('academic_level_id');
            });
        }

        // Backfill branch_id from the legacy branch string column, if it still exists.
        if (Schema::hasColumn('classes', 'branch')) {
            $branches = DB::table('branches')->pluck('id', 'name')->all();

            foreach ($branches as $name => $id) {
                DB::table('classes')->where('branch', $name)->update(['branch_id' => $id]);
            }

            $unmatched = DB::table('classes')->whereNull('branch_id')->pluck('branch')->unique()->filter()->values();

            foreach ($unmatched as $name) {
                $id = DB::table('branches')->insertGetId([
                    'name' => $name,
                    'code' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('classes')->where('branch', $name)->update(['branch_id' => $id]);
            }
        }

        // Assign an academic level based on the class name.
        $levels = DB::table('academic_levels')->pluck('id', 'name');

        foreach (DB::table('classes')->whereNull('academic_level_id')->pluck('name', 'id') as $id => $name) {
            $levelId = null;

            if (preg_match('/pp\s*(\d+)|k\s*(\d+)|grade\s*(\d+)/i', (string) $name, $m)) {
                $n = (int) ($m[1] ?: ($m[2] ?: $m[3]));

                if (preg_match('/^(pp|k)\s*\d+/i', (string) $name) || $n <= 3) {
                    $levelId = $levels['Early Years Education'] ?? null;
                } elseif ($n <= 6) {
                    $levelId = $levels['Upper Primary'] ?? null;
                } else {
                    $levelId = $levels['Junior School'] ?? null;
                }
            } else {
                $levelId = $levels['Early Years Education'] ?? null;
            }

            DB::table('classes')->where('id', $id)->update(['academic_level_id' => $levelId]);
        }

        // Drop the legacy nullable FKs if they still exist, then re-create them NOT NULL with restrictOnDelete.
        if ($this->foreignKeyExists('classes', 'classes_branch_id_foreign')) {
            Schema::table('classes', fn (Blueprint $table) => $table->dropForeign(['branch_id']));
        }

        if ($this->foreignKeyExists('classes', 'classes_academic_level_id_foreign')) {
            Schema::table('classes', fn (Blueprint $table) => $table->dropForeign(['academic_level_id']));
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable(false)->change();
            $table->foreignId('academic_level_id')->nullable(false)->change();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('academic_level_id')->references('id')->on('academic_levels')->restrictOnDelete();
        });

        if (Schema::hasColumn('classes', 'branch')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropColumn('branch');
            });
        }

        $uniqueKeyExists = collect(Schema::getIndexes('classes'))
            ->where('name', 'classes_branch_name_unique')
            ->isNotEmpty();

        if (! $uniqueKeyExists) {
            Schema::table('classes', function (Blueprint $table) {
                $table->unique(['branch_id', 'name'], 'classes_branch_name_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_branch_name_unique');
            $table->string('branch')->nullable();
        });

        $branches = DB::table('branches')->pluck('name', 'id');

        foreach ($branches as $id => $name) {
            DB::table('classes')->where('branch_id', $id)->update(['branch' => $name]);
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('academic_level_id');
            $table->dropColumn('is_active');
        });
    }
};
