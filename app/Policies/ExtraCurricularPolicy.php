<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExtraCurricularPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('extra_curriculars.view');
    }

    public function view(User $user, ExtraCurricular $model): bool
    {
        return $user->can('extra_curriculars.view');
    }

    public function create(User $user): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function update(User $user, ExtraCurricular $model): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function delete(User $user, ExtraCurricular $model): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function forceDelete(User $user, ExtraCurricular $model): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function restore(User $user, ExtraCurricular $model): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('extra_curriculars.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('extra_curriculars.manage');
    }
}
