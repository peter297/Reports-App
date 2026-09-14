<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            'Deputy HeadTeacher',
            'Dean of Islamic Studies',
            'Coordinators',
            'School Administrator',
            'Head of Admissions',
            'Teachers',
            'Deputy Principal',
            'Fleet Manager',
            'CEO',
            'Principal',
            'HeadTeacher',
            'ICT Department',
            'Admin',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function down(): void
    {
        Role::whereIn('name', [
            'Deputy HeadTeacher',
            'Dean of Islamic Studies',
            'Coordinators',
            'School Administrator',
            'Head of Admissions',
            'Teachers',
            'Deputy Principal',
            'Fleet Manager',
            'CEO',
            'Principal',
            'HeadTeacher',
            'ICT Department',
            'Admin',
        ])->delete();
    }
};
