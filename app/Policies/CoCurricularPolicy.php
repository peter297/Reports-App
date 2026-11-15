<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoCurricularPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('co_curriculars.view');
    }

    public function view(User $user, CoCurricular $model): bool
    {
        return $user->can('co_curriculars.view');
    }

    public function create(User $user): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function update(User $user, CoCurricular $model): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function delete(User $user, CoCurricular $model): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function forceDelete(User $user, CoCurricular $model): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function restore(User $user, CoCurricular $model): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('co_curriculars.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('co_curriculars.manage');
    }
}
