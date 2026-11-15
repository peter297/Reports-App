<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, Attendance $model): bool
    {
        return $user->can('attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attendances.manage');
    }

    public function update(User $user, Attendance $model): bool
    {
        return $user->can('attendances.manage');
    }

    public function delete(User $user, Attendance $model): bool
    {
        return $user->can('attendances.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('attendances.manage');
    }

    public function forceDelete(User $user, Attendance $model): bool
    {
        return $user->can('attendances.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('attendances.manage');
    }

    public function restore(User $user, Attendance $model): bool
    {
        return $user->can('attendances.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('attendances.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('attendances.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('attendances.manage');
    }
}
