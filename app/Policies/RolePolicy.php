<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function view(User $user, Role $model): bool
    {
        return $user->can('roles.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function update(User $user, Role $model): bool
    {
        return $user->can('roles.manage');
    }

    public function delete(User $user, Role $model): bool
    {
        return $user->can('roles.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function forceDelete(User $user, Role $model): bool
    {
        return $user->can('roles.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function restore(User $user, Role $model): bool
    {
        return $user->can('roles.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('roles.manage');
    }
}
