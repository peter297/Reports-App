<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::findOrCreate('super_admin', 'web');

        $user = User::updateOrCreate(
            ['email' => 'admin@alameenacademy.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password123!',
                'branch' => null,
                'line_manager_id' => null,
            ],
        );

        $user->syncRoles(['super_admin']);
    }
}
