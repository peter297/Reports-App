<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TermPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('sessions.view');
    }

    public function view(User $user, Term $model): bool
    {
        return $user->can('sessions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sessions.manage');
    }

    public function update(User $user, Term $model): bool
    {
        return $user->can('sessions.manage');
    }

    public function delete(User $user, Term $model): bool
    {
        return $user->can('sessions.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('sessions.manage');
    }

    public function forceDelete(User $user, Term $model): bool
    {
        return $user->can('sessions.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('sessions.manage');
    }

    public function restore(User $user, Term $model): bool
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
