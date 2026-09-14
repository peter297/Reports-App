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
        Schema::table('item_requests', function (Blueprint $table) {
            $table->json('items')->after('week_id')->nullable(); // Add new column
            $table->dropColumn(['item_name', 'estimate_cost']);  // Remove old columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            $table->dropColumn('items'); // Remove new column
            $table->string('item_name')->nullable();
            $table->integer('estimate_cost')->nullable();
        });
    }
};
