<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', ['employees.view', 'employees.manage', 'departments.view', 'departments.manage'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Recreate the permission rows on rollback for reversibility.
        foreach ([
            ['employees.view', 'View employees'],
            ['employees.manage', 'Manage employees'],
            ['departments.view', 'View departments'],
            ['departments.manage', 'Manage departments'],
        ] as [$name, $description]) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
