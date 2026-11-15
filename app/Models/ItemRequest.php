<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_session_id',
        'term_id',
        'week_id',
        'items', // Store multiple items as JSON
        'description',
        'remarks',
        'user_id',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function year_session(): BelongsTo
    {
        return $this->belongsTo(YearSession::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('item_requests.manage')) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function recipients()
    {
        return $this->belongsToMany(User::class, 'request_user', 'item_request_id', 'user_id');
    }
}
