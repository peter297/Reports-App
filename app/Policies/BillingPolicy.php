<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billings.view');
    }

    public function view(User $user, Billing $model): bool
    {
        return $user->can('billings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('billings.manage');
    }

    public function update(User $user, Billing $model): bool
    {
        return $user->can('billings.manage');
    }

    public function delete(User $user, Billing $model): bool
    {
        return $user->can('billings.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('billings.manage');
    }

    public function forceDelete(User $user, Billing $model): bool
    {
        return $user->can('billings.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('billings.manage');
    }

    public function restore(User $user, Billing $model): bool
    {
        return $user->can('billings.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('billings.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('billings.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('billings.manage');
    }
}
