<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dead = DB::table('permissions')->where('name', 'page_SyllabusDashboard')->first();

        if ($dead) {
            DB::table('role_has_permissions')->where('permission_id', $dead->id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $dead->id)->delete();
            DB::table('permissions')->where('id', $dead->id)->delete();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $id = DB::table('permissions')->insertGetId([
            'name' => 'page_SyllabusDashboard',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $coordinators = DB::table('roles')->where('name', 'Coordinator')->first();

        if ($coordinators) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $id,
                'role_id' => $coordinators->id,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
