<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectAssignment extends Model
{
    use HasFactory;

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_COORDINATOR = 'coordinator';

    protected $fillable = [
        'year_session_id',
        'subject_id',
        'class_id',
        'stream_id',
        'user_id',
        'assignment_role',
        'branch',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function yearSession(): BelongsTo
    {
        return $this->belongsTo(YearSession::class, 'year_session_id');
    }

    public static function roles(): array
    {
        return [
            self::ROLE_TEACHER => 'Teacher',
            self::ROLE_COORDINATOR => 'Coordinator',
        ];
    }
}
