<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);
        $this->call(SchoolStructureSeeder::class);
        $this->call(GoalSheetsSeeder::class);
        $this->call(RolePermissionSeeder::class);
        $this->call(ReportingAssignmentsSeeder::class);
        $this->call(ReportTemplatesSeeder::class);
        $this->call(OrganizationUsersSeeder::class);
    }
}
