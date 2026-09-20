<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendances')
            ->where('section', 'Early Years - EYE')
            ->update(['section' => 'EYE']);

        DB::table('section_coordinator_assignments')
            ->where('section', 'Early Years - EYE')
            ->update(['section' => 'EYE']);
    }

    public function down(): void
    {
        DB::table('attendances')
            ->where('section', 'EYE')
            ->update(['section' => 'Early Years - EYE']);

        DB::table('section_coordinator_assignments')
            ->where('section', 'EYE')
            ->update(['section' => 'Early Years - EYE']);
    }
};
