<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->where('name', $name)->isNotEmpty();
    }

    public function up(): void
    {
        // Step 1: create an index with the new (stream-aware) definition so the FK can be re-rooted.
        if (! $this->indexExists('syllabus_mappings', 'syllabus_mappings_lookup_new')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->index(['year_session_id', 'term_id', 'class_id', 'stream_id', 'subject_id'], 'syllabus_mappings_lookup_new');
            });
        }

        // Step 2: drop the legacy index (the FK can be satisfied by the new one).
        if ($this->indexExists('syllabus_mappings', 'syllabus_mappings_lookup')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->dropIndex('syllabus_mappings_lookup');
            });
        }

        // Step 3: drop the legacy branch column.
        if (Schema::hasColumn('syllabus_mappings', 'branch')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->dropColumn('branch');
            });
        }

        // Step 4: finalise names — create the target index, then drop the temporary one.
        if (! $this->indexExists('syllabus_mappings', 'syllabus_mappings_lookup')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->index(['year_session_id', 'term_id', 'class_id', 'stream_id', 'subject_id'], 'syllabus_mappings_lookup');
            });
        }

        if ($this->indexExists('syllabus_mappings', 'syllabus_mappings_lookup_new')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->dropIndex('syllabus_mappings_lookup_new');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('syllabus_mappings', 'syllabus_mappings_lookup')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->dropIndex('syllabus_mappings_lookup');
            });
        }

        if (! Schema::hasColumn('syllabus_mappings', 'branch')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->string('branch')->nullable();
            });

            $mappings = \Illuminate\Support\Facades\DB::table('syllabus_mappings')
                ->join('classes', 'classes.id', '=', 'syllabus_mappings.class_id')
                ->join('branches', 'branches.id', '=', 'classes.branch_id')
                ->get(['syllabus_mappings.id', 'branches.name as branch']);

            foreach ($mappings as $mapping) {
                \Illuminate\Support\Facades\DB::table('syllabus_mappings')->where('id', $mapping->id)->update(['branch' => $mapping->branch]);
            }
        }

        if (! $this->indexExists('syllabus_mappings', 'syllabus_mappings_lookup')) {
            Schema::table('syllabus_mappings', function (Blueprint $table) {
                $table->index(['year_session_id', 'term_id', 'class_id', 'subject_id', 'branch'], 'syllabus_mappings_lookup');
            });
        }
    }
};
