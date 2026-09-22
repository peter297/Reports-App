<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_session_id',
        'name',
        'is_active',

    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function year_session(): BelongsTo
    {
        return $this->belongsTo(YearSession::class);
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(Week::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function active(): ?self
    {
        return static::query()->active()->latest('id')->first();
    }

    protected static function booted(): void
    {
        static::saving(function (Term $term): void {
            if ($term->is_active) {
                static::query()
                    ->whereKeyNot($term->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                if ($term->year_session_id) {
                    YearSession::query()
                        ->whereKey($term->year_session_id)
                        ->where('is_active', false)
                        ->update(['is_active' => true]);

                    YearSession::query()
                        ->whereKeyNot($term->year_session_id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);
                }
            }
        });
    }
}
