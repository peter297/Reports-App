<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplateVersion extends Model
{
    protected $fillable = [
        'report_template_id',
        'version',
        'change_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ReportTemplateSection::class, 'report_template_version_id')->orderBy('sort_order')->orderBy('id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'template_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
