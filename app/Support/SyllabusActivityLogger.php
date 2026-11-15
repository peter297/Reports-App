<?php

namespace App\Support;

use App\Models\SyllabusActivityLog;
use Illuminate\Support\Facades\Auth;

class SyllabusActivityLogger
{
    public static function record(string $recordType, ?int $recordId, string $action, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null): SyllabusActivityLog
    {
        return SyllabusActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'record_type' => $recordType,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
