<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingAssignment extends Model
{
    protected $fillable = [
        'submitter_role_id',
        'branch_id',
        'academic_level_id',
        'recipient_role_id',
        'recipient_user_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function submitterRole(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'submitter_role_id');
    }

    public function recipientRole(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'recipient_role_id');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }
}
