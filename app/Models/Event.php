<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'calendar_path', 'event_date', 'end_date', 'status', 'branch', 'is_active',
        'in_charge', 'term_id', 'event_week',
    ];

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasUnrestrictedAccess()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->whereNull('branch')
                ->orWhereIn('branch', $user->accessibleBranchNames());
        });
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            $user = \Illuminate\Support\Facades\Auth::hasUser() ? \Illuminate\Support\Facades\Auth::user() : null;

            if (! $user || $user->hasUnrestrictedAccess()) {
                return;
            }

            $branch = $event->branch;

            abort_unless(
                $user->can('events.manage')
                    && ($branch === null || in_array($branch, $user->accessibleBranchNames(), true)),
                403,
                'You can only manage events for your assigned branch.'
            );
        });
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'class_event', 'event_id', 'class_id');
    }

    public function week()
    {
        return $this->belongsTo(Week::class, 'event_week');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id'); // Ensure 'term_id' matches your column name
    }
}
