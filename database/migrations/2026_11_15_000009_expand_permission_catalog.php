<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Expand the permission catalog to cover every existing module.
     * Idempotent: only missing permissions are created and role grants are re-synced.
     */
    public function up(): void
    {
        (new \Database\Seeders\RolePermissionSeeder())->run();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permissions are intentionally retained on rollback: dropping them
        // would strip role assignments that may now be in use.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
