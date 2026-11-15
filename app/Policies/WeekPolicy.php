<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WeekPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('sessions.view');
    }

    public function view(User $user, Week $model): bool
    {
        return $user->can('sessions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sessions.manage');
    }

    public function update(User $user, Week $model): bool
    {
        return $user->can('sessions.manage');
    }

    public function delete(User $user, Week $model): bool
    {
        return $user->can('sessions.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('sessions.manage');
    }

    public function forceDelete(User $user, Week $model): bool
    {
        return $user->can('sessions.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('sessions.manage');
    }

    public function restore(User $user, Week $model): bool
    {
        return $user->can('sessions.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('sessions.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('sessions.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('sessions.manage');
    }
}
