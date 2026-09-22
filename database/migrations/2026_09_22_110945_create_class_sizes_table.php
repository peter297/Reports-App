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
        Schema::create('class_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('branch');
            $table->string('section');
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->unsignedInteger('total_boys')->default(0);
            $table->unsignedInteger('total_girls')->default(0);
            $table->unsignedInteger('class_total')->default(0);
            $table->timestamps();

            $table->unique(['branch', 'section', 'class_id', 'stream_id'], 'class_sizes_scope_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sizes');
    }
};
