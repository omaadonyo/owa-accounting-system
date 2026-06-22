<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function log(string $action, ?string $description = null, ?Model $subject = null, ?array $properties = null): void
    {
        $user = auth()->user();

        ActivityLog::create([
            'user_id' => $user?->id,
            'business_id' => currentBusinessId(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
