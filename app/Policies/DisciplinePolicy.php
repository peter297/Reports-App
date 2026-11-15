<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisciplinePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('discipline.view');
    }

    public function view(User $user, Discipline $model): bool
    {
        return $user->can('discipline.view');
    }

    public function create(User $user): bool
    {
        return $user->can('discipline.manage');
    }

    public function update(User $user, Discipline $model): bool
    {
        return $user->can('discipline.manage');
    }

    public function delete(User $user, Discipline $model): bool
    {
        return $user->can('discipline.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('discipline.manage');
    }

    public function forceDelete(User $user, Discipline $model): bool
    {
        return $user->can('discipline.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('discipline.manage');
    }

    public function restore(User $user, Discipline $model): bool
    {
        return $user->can('discipline.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('discipline.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('discipline.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('discipline.manage');
    }
}
