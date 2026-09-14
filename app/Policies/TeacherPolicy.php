<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return false;
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
