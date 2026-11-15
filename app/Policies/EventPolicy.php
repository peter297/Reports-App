<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('events.view');
    }

    public function view(User $user, Event $model): bool
    {
        return $user->can('events.view');
    }

    public function create(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function update(User $user, Event $model): bool
    {
        return $user->can('events.manage');
    }

    public function delete(User $user, Event $model): bool
    {
        return $user->can('events.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function forceDelete(User $user, Event $model): bool
    {
        return $user->can('events.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function restore(User $user, Event $model): bool
    {
        return $user->can('events.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('events.manage');
    }
}
