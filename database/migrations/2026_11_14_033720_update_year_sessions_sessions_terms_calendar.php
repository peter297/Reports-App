<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Academic calendar: sessions and terms get dates and status ---
        Schema::table('year_sessions', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('status')->default('upcoming')->after('end_date');
        });

        DB::table('year_sessions')->where('is_active', true)->update(['status' => 'active']);
        DB::table('year_sessions')->where('is_active', false)->update(['status' => 'upcoming']);

        Schema::table('year_sessions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('status')->default('upcoming')->after('end_date');
        });

        DB::table('terms')->where('is_active', true)->update(['status' => 'active']);
        DB::table('terms')->where('is_active', false)->update(['status' => 'upcoming']);

        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        // --- Weeks get a unique sequential number per term ---
        Schema::table('weeks', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable()->after('term_id');
        });

        foreach (DB::table('weeks')->get() as $week) {
            $number = null;

            if (preg_match('/(\d+)/', (string) $week->name, $m)) {
                $number = (int) $m[1];
            }

            DB::table('weeks')->where('id', $week->id)->update(['number' => $number ?? 0]);
        }

        // Resolve any collisions (name without number): renumber sequentially within each term.
        foreach (DB::table('terms')->get() as $term) {
            $weeks = DB::table('weeks')->where('term_id', $term->id)->orderBy('start_date')->orderBy('id')->get();
            $n = 0;

            foreach ($weeks as $week) {
                $n++;
                DB::table('weeks')->where('id', $week->id)->update(['number' => $n]);
            }
        }

        Schema::table('weeks', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable(false)->change();
            $table->unique(['term_id', 'number'], 'weeks_term_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('weeks', function (Blueprint $table) {
            $table->dropUnique('weeks_term_number_unique');
            $table->dropColumn('number');
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
        });
        DB::table('terms')->where('status', 'active')->update(['is_active' => true]);
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'status']);
        });

        Schema::table('year_sessions', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
        });
        DB::table('year_sessions')->where('status', 'active')->update(['is_active' => true]);
        Schema::table('year_sessions', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'status']);
        });
    }
};
