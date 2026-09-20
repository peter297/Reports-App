<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'file_path',
        'file_type',
        'file_size',
    ];

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
