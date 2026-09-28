<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'file_path',
        'file_type',
        'file_size',
        'link_url',
    ];

    public const CATEGORIES = ['Report Template', 'Trackers', 'MAL'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'report_template_role');
    }

    /**
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasUnrestrictedAccess()) {
            return $query;
        }

        $roleIds = $user->roles()->pluck('roles.id')->all();

        return $query->where(function (Builder $query) use ($roleIds): void {
            $query->whereDoesntHave('roles')
                ->orWhereHas('roles', fn (Builder $query): Builder => $query->whereIn('roles.id', $roleIds));
        });
    }

    /**
     * Get the file extension based on the file type.
     */
    public function getExtensionAttribute(): string
    {
        return match ($this->file_type) {
            'word' => 'docx',
            'excel' => 'xlsx',
            default => 'bin',
        };
    }

    /**
     * Get a human-readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        if ($this->file_size === null) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }

    /**
     * Get the download URL for this template.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('report-templates.download', $this);
    }
}
