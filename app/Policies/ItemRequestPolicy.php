<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ItemRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('item_requests.view');
    }

    public function view(User $user, ItemRequest $model): bool
    {
        return $user->can('item_requests.view');
    }

    public function create(User $user): bool
    {
        return $user->can('item_requests.manage');
    }

    public function update(User $user, ItemRequest $model): bool
    {
        return $user->can('item_requests.manage');
    }

    public function delete(User $user, ItemRequest $model): bool
    {
        return $user->can('item_requests.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('item_requests.manage');
    }

    public function forceDelete(User $user, ItemRequest $model): bool
    {
        return $user->can('item_requests.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('item_requests.manage');
    }

    public function restore(User $user, ItemRequest $model): bool
    {
        return $user->can('item_requests.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('item_requests.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('item_requests.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('item_requests.manage');
    }
}
