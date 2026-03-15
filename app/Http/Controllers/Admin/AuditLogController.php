<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user')->latest('created_at');

        if ($event = $request->query('event')) {
            $query->where('event', $event);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        return view('admin.audit-logs.index', [
            'auditLogs' => $query->paginate(50)->withQueryString(),
            'currentEvent' => $event,
            'currentSearch' => $search,
            'eventOptions' => AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event'),
        ]);
    }
}