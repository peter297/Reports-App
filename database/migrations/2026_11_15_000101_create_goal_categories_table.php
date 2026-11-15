<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('goal_measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_category_id')->constrained('goal_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('goal_statement')->nullable();
            $table->string('uom')->nullable()->default('%');
            $table->text('calculation_method')->nullable();
            $table->string('source_of_data')->nullable();
            $table->string('achievement_type', 2)->default('HB'); // HB = higher is better, LB = lower is better
            $table->decimal('performance_weight', 6, 4)->nullable();
            $table->decimal('goal_for_year', 6, 2)->nullable(); // percent, e.g. 80 = 80%
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['goal_category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_measures');
        Schema::dropIfExists('goal_categories');
    }
};
