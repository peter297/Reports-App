<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'title',        'template_id',
        'template_version_id',
        'role_id',
        'branch_id',
        'academic_level_id',
        'period_start',
        'period_end',
        'deadline',
        'frequency',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'current_revision',
        'is_consolidated',
        'compilation_kind',
        'compiled_by_user_id',
        'category',
        'subject',
        'summary',
        'file_paths',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'deadline' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'current_revision' => 'integer',
            'is_consolidated' => 'boolean',
            'file_paths' => 'array',
        ];
    }

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(ReportTemplateVersion::class, 'template_version_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'academic_level_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ReportSectionResponse::class)->orderBy('sort_order')->orderBy('id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ReportRevision::class)->orderByDesc('revision_number');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ReportReview::class)->orderBy('created_at');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'report_recipients', 'report_id', 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function compilation(): HasOne
    {
        return $this->hasOne(ReportCompilation::class, 'report_id');
    }

    public function compiledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compiled_by_user_id');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    /**
     * Reports visible to the given user:
     *  - org-wide viewers (CEO / users with reports.view_all), or
     *  - reports routed to the user, or
     *  - reports the user submitted.
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('reports.view_all')) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('reports.user_id', $user->id)
                ->orWhereHas('recipients', fn (Builder $q) => $q->where('users.id', $user->id));
        });
    }

    /** Reports routed to the user that are awaiting their action. */
    public function scopePendingFor(Builder $query, User $user): Builder
    {
        return $query->whereHas('recipients', fn (Builder $q) => $q->where('users.id', $user->id))
            ->whereIn('status', [ReportStatus::Submitted->value, ReportStatus::Resubmitted->value]);
    }

    /** Reports submitted by the given role for a period (used by trackers). */
    public function scopeForPeriod(Builder $query, string $periodStart, string $periodEnd): Builder
    {
        return $query->whereDate('period_start', '<=', $periodEnd)
            ->whereDate('period_end', '>=', $periodStart);
    }

    public function isDraft(): bool
    {
        return $this->status === ReportStatus::Draft;
    }

    public function isEditableBy(User $user): bool
    {
        return $this->user_id === $user->id
            && in_array($this->status, [ReportStatus::Draft, ReportStatus::Returned], true);
    }
}
