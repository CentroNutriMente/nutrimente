<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /** Registro operazioni: riservato ad admin / founder. */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->hasRole('admin') || $user->professionalProfile?->is_founder, 403);

        $logs = AuditLog::with('user:id,name')
            ->when($request->action, fn ($q) => $q->where('action', $request->action))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (AuditLog $l) => [
                'id'      => $l->id,
                'action'  => $l->action,
                'user'    => $l->user?->name ?? '—',
                'model'   => $l->model_type ? class_basename($l->model_type) : null,
                'modelId' => $l->model_id,
                'email'   => $l->new_values['email'] ?? null,
                'ip'      => $l->ip_address,
                'at'      => $l->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('Audit/Index', [
            'logs'    => $logs,
            'filters' => $request->only('action'),
        ]);
    }
}
