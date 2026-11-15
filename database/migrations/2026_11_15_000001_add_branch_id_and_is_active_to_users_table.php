<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('email')->constrained('branches')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('branch_id');
        });

        // Migrate the legacy string branch column to the FK.
        $branches = Illuminate\Support\Facades\DB::table('branches')->pluck('id', 'name');
        foreach (Illuminate\Support\Facades\DB::table('users')->whereNotNull('branch')->get(['id', 'branch']) as $user) {
            $branchId = $branches[$user->branch] ?? null;
            Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['branch_id' => $branchId]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('branch');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('branch')->nullable();
        });

        $branches = Illuminate\Support\Facades\DB::table('branches')->pluck('name', 'id');
        foreach (Illuminate\Support\Facades\DB::table('users')->whereNotNull('branch_id')->get(['id', 'branch_id']) as $user) {
            Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['branch' => $branches[$user->branch_id] ?? null]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('is_active');
        });
    }
};
