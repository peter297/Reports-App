<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // First, make the column nullable to prevent errors
            $table->date('event_date')->nullable()->after('description');
        });

        // Optionally, update existing rows with a valid date
        \DB::statement("UPDATE events SET event_date = CURDATE() WHERE event_date IS NULL");

        Schema::table('events', function (Blueprint $table) {
            // Then, make it NOT NULL after updating existing records
            $table->date('event_date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('event_date');
        });
    }
};
