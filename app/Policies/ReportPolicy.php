<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('reports.module.view');
    }

    public function view(User $user, Report $report): bool
    {
        return $user->can('reports.view_all')
            || $user->id === $report->user_id
            || $report->recipients()->where('users.id', $user->id)->exists()
            || $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->can('reports.create');
    }

    public function update(User $user, Report $report): bool
    {
        return $user->can('reports.create') && $report->isEditableBy($user);
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->isSuperAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Report $report): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Report $report): bool
    {
        return $user->isSuperAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function replicate(User $user): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
