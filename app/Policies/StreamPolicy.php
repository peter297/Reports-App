<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StreamPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('academic_structure.view');
    }

    public function view(User $user, Stream $model): bool
    {
        return $user->can('academic_structure.view');
    }

    public function create(User $user): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function update(User $user, Stream $model): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function delete(User $user, Stream $model): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function forceDelete(User $user, Stream $model): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function restore(User $user, Stream $model): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function replicate(User $user): bool
    {
        return $user->can('academic_structure.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('academic_structure.manage');
    }
}
