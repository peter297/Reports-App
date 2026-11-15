<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('maintenance.view');
    }

    public function view(User $user, Maintenance $model): bool
    {
        return $user->can('maintenance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('maintenance.manage');
    }

    public function update(User $user, Maintenance $model): bool
    {
        return $user->can('maintenance.manage');
    }

    public function delete(User $user, Maintenance $model): bool
    {
        return $user->can('maintenance.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('maintenance.manage');
    }

    public function forceDelete(User $user, Maintenance $model): bool
    {
        return $user->can('maintenance.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('maintenance.manage');
    }

    public function restore(User $user, Maintenance $model): bool
    {
        return $user->can('maintenance.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('maintenance.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('maintenance.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('maintenance.manage');
    }
}
