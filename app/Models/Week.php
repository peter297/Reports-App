<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Week extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_id',
        'number',
        'name',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected function getResolvedEndDateAttribute(): ?\Carbon\Carbon
    {
        return $this->resolvedEndDate();
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function containsDate(mixed $date): bool
    {
        $day = static::parseDay($date);

        if (! $day || ! $this->start_date || ! $this->end_date) {
            return false;
        }

        return $this->start_date->toDateString() <= $day
            && $day <= $this->end_date->toDateString();
    }

    public function resolvedEndDate(): ?\Carbon\Carbon
    {
        if ($this->end_date) {
            return $this->end_date;
        }

        return $this->start_date?->copy()->addDays(4);
    }

    public function scopeContaining($query, mixed $date)
    {
        $day = static::parseDay($date);

        if (! $day) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereDate('start_date', '<=', $day)->whereDate('end_date', '>=', $day);
    }

    public static function forDate(mixed $date, ?int $termId = null): ?self
    {
        $day = static::parseDay($date);

        if (! $day) {
            return null;
        }

        $query = static::query()->containing($day)->orderByDesc('start_date');

        if ($termId) {
            $query->where('term_id', $termId);
        }

        return $query->get()->first(fn (Week $week): bool => $week->containsDate($day));
    }

    protected static function parseDay(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return $date instanceof \DateTimeInterface
                ? \Carbon\Carbon::instance($date)->toDateString()
                : \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
