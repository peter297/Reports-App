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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['Pending', 'Upcoming', 'Completed', 'Postponed'])->default('Pending');
            $table->boolean('is_active')->default(true);
            $table->string('in_charge')->nullable(); // Responsible person
            $table->integer('event_week')->nullable(); // Week of the term
            $table->enum('term', ['Term 1', 'Term 2', 'Term 3'])->nullable(); // Academic term
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
