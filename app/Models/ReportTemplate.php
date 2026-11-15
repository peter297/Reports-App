<?php

namespace App\Models;

use App\Enums\ReportFrequency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReportTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'role_id',
        'frequency',
        'is_active',
        'current_version_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => ReportFrequency::class,
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ReportTemplateVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReportTemplateVersion::class, 'report_template_id')->orderByDesc('version');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ReportTemplateSection::class, 'report_template_version_id', 'current_version_id')->orderBy('sort_order');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForRole(Builder $query, int $roleId): Builder
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Create a new version from a list of section definitions and make it current.
     *
     * @param  array<int, array{title: string, sort_order?: int, is_mandatory?: bool, is_auto?: bool, data_source?: ?string, help_text?: ?string}>  $sections
     */
    public function publishVersion(array $sections, ?string $changeNote, ?int $createdBy = null): ReportTemplateVersion
    {
        return \DB::transaction(function () use ($sections, $changeNote, $createdBy): ReportTemplateVersion {
            $this->loadMissing('versions');
            $versionNumber = ((int) $this->versions()->max('version')) + 1;

            $version = $this->versions()->create([
                'version' => $versionNumber,
                'change_note' => $changeNote,
                'created_by' => $createdBy,
            ]);

            foreach (array_values($sections) as $i => $section) {
                $version->sections()->create([
                    'title' => $section['title'],
                    'sort_order' => $section['sort_order'] ?? ($i + 1),
                    'is_mandatory' => (bool) ($section['is_mandatory'] ?? true),
                    'is_auto' => (bool) ($section['is_auto'] ?? false),
                    'data_source' => $section['data_source'] ?? null,
                    'help_text' => $section['help_text'] ?? null,
                ]);
            }

            $this->forceFill(['current_version_id' => $version->id])->save();

            return $version;
        });
    }
}
