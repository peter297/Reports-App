<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassSizePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, ClassSize $model): bool
    {
        return $user->can('attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attendances.manage');
    }

    public function update(User $user, ClassSize $model): bool
    {
        return $user->can('attendances.manage');
    }

    public function delete(User $user, ClassSize $model): bool
    {
        return $user->can('attendances.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('attendances.manage');
    }

    public function forceDelete(User $user, ClassSize $model): bool
    {
        return $user->can('attendances.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('attendances.manage');
    }

    public function restore(User $user, ClassSize $model): bool
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
