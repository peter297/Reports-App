<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the initial reference data (idempotent).
        $now = now();

        $branches = [
            ['name' => 'Juja Road', 'code' => 'JUJA'],
            ['name' => 'Kitisuru', 'code' => 'KITI'],
            ['name' => 'South C', 'code' => 'SOUTH'],
        ];

        foreach ($branches as $branch) {
            if (! \Illuminate\Support\Facades\DB::table('branches')->where('name', $branch['name'])->exists()) {
                \Illuminate\Support\Facades\DB::table('branches')->insert([
                    'name' => $branch['name'],
                    'code' => $branch['code'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $levels = [
            ['name' => 'Early Years Education', 'sort_order' => 1],
            ['name' => 'Upper Primary', 'sort_order' => 2],
            ['name' => 'Junior School', 'sort_order' => 3],
        ];

        foreach ($levels as $level) {
            if (! \Illuminate\Support\Facades\DB::table('academic_levels')->where('name', $level['name'])->exists()) {
                \Illuminate\Support\Facades\DB::table('academic_levels')->insert([
                    'name' => $level['name'],
                    'sort_order' => $level['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_levels');
        Schema::dropIfExists('branches');
    }
};
