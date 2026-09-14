<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('branch')->nullable()->after('email');
            $table->foreignId('line_manager_id')
                ->nullable()
                ->after('branch')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['line_manager_id']);
            $table->dropColumn(['branch', 'line_manager_id']);
        });
    }
};
