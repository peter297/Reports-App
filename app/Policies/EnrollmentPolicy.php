<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('enrollments.view');
    }

    public function view(User $user, Enrollment $model): bool
    {
        return $user->can('enrollments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('enrollments.manage');
    }

    public function update(User $user, Enrollment $model): bool
    {
        return $user->can('enrollments.manage');
    }

    public function delete(User $user, Enrollment $model): bool
    {
        return $user->can('enrollments.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('enrollments.manage');
    }

    public function forceDelete(User $user, Enrollment $model): bool
    {
        return $user->can('enrollments.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('enrollments.manage');
    }

    public function restore(User $user, Enrollment $model): bool
    {
        return $user->can('enrollments.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('enrollments.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('enrollments.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('enrollments.manage');
    }
}
