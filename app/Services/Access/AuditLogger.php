<?php

namespace App\Services\Access;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public function log(string $event, ?User $user = null, ?array $details = null, ?Request $request = null): void
    {
        try {
            AuditLog::create([
                'user_id' => $user?->id,
                'event' => $event,
                'details' => $details,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to persist audit log entry.', [
                'event' => $event,
                'user_id' => $user?->id,
                'details' => $details,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}