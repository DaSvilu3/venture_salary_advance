<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:60']]);

        $logs = AuditLog::query()
            ->with('actor:id,name')
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', 'like', "{$action}%"))
            ->latest('id')
            ->paginate(40)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->actor?->name,
                'subject' => $log->subject_type === null ? null : class_basename($log->subject_type).' #'.$log->subject_id,
                'meta' => $log->meta,
                'at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/audit-log', [
            'logs' => $logs,
            'filters' => ['action' => $filters['action'] ?? ''],
        ]);
    }
}
