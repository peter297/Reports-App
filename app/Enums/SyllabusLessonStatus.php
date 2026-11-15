<?php

namespace App\Enums;

enum SyllabusLessonStatus: string
{
    case Completed = 'completed';
    case Overdue = 'overdue';
    case Upcoming = 'upcoming';
    case NotScheduled = 'not_scheduled';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'COMPLETED',
            self::Overdue => 'OVERDUE',
            self::Upcoming => 'UPCOMING',
            self::NotScheduled => 'NOT SCHEDULED',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Overdue => 'danger',
            self::Upcoming => 'warning',
            self::NotScheduled => 'gray',
        };
    }

    public static function fromProgress(?string $completionDate, ?string $expectedDate, ?\DateTimeInterface $today = null): self
    {
        if ($completionDate) {
            return self::Completed;
        }

        if (! $expectedDate) {
            return self::NotScheduled;
        }

        $today ??= now();
        $expected = \Illuminate\Support\Carbon::parse($expectedDate)->startOfDay();

        return $expected->lt($today->copy()->startOfDay()) ? self::Overdue : self::Upcoming;
    }
}
