<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_sheet_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('completed'); // completed | failed
            $table->json('summary')->nullable();   // totals: created / updated / skipped / errors
            $table->json('errors')->nullable();    // list of {worksheet, row, message}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_sheet_imports');
    }
};
