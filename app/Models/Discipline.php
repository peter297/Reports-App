<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discipline extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_session_id',
        'term_id',
        'week_id',
        'class_id',
        'stream_id',
        'case_name',
        'learner_name',
        'description',
        'person_responsible',
        'action_taken',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasUnrestrictedAccess()) {
            return $query;
        }

        return $query->whereHas('class', fn (Builder $query) => $query->whereIn('branch_id', $user->accessibleBranchIds()));
    }

    protected static function booted(): void
    {
        static::saving(function (Discipline $discipline): void {
            $user = \Illuminate\Support\Facades\Auth::hasUser() ? \Illuminate\Support\Facades\Auth::user() : null;

            if (! $user || $user->hasUnrestrictedAccess()) {
                return;
            }

            $branchId = $discipline->class?->branch_id;

            abort_unless(
                $user->can('discipline.manage')
                    && in_array((int) $branchId, $user->accessibleBranchIds(), true),
                403,
                'You can only manage discipline records for your assigned branch.'
            );
        });
    }
}
