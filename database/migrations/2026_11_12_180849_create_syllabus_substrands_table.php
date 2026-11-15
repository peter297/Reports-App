<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_substrands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syllabus_strand_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['syllabus_strand_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_substrands');
    }
};
