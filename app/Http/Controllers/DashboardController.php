<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ContactRequest;
use App\Models\Group;
use App\Models\GroupEnrollmentRequest;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $myPatients = Patient::where(fn ($q) => $q
            ->where('created_by', $user->id)
            ->orWhereHas('professionals', fn ($q2) => $q2->where('user_id', $user->id)));

        // ── Statistiche (riga in basso del mockup) ──────────────────────────
        $activePatients = (clone $myPatients)->where('is_active', true)->count();
        $newPatients = (clone $myPatients)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $sessionsDone = Appointment::where('user_id', $user->id)
            ->whereMonth('start_at', now()->month)->whereYear('start_at', now()->year)
            ->where('start_at', '<=', now())
            ->count();

        $invoicesMonthQuery = Invoice::where('user_id', $user->id)
            ->whereMonth('issued_at', now()->month)->whereYear('issued_at', now()->year)
            ->whereNotIn('status', ['cancelled']);

        $stats = [
            'active_patients' => $activePatients,
            'new_patients'    => $newPatients,
            'sessions_done'   => $sessionsDone,
            'revenue_month'   => (float) (clone $invoicesMonthQuery)->sum('total'),
            'invoices_month'  => (clone $invoicesMonthQuery)->count(),
        ];

        // ── Agenda di oggi ──────────────────────────────────────────────────
        $todayAppointments = Appointment::with('patient:id,first_name,last_name')
            ->where('user_id', $user->id)
            ->whereDate('start_at', today())
            ->orderBy('start_at')
            ->get()
            ->map(fn ($a) => [
                'id'     => $a->id,
                'time'   => $a->start_at->format('H:i'),
                'who'    => $a->patient ? mb_substr($a->patient->first_name, 0, 1).'.'.mb_substr($a->patient->last_name, 0, 1).'.' : ($a->title ?? '—'),
                'label'  => $a->type === 'session' ? 'Colloquio' : ($a->title ?: ucfirst((string) $a->type)),
                'online' => str_contains(mb_strtolower((string) ($a->room ?? '')), 'online') || $a->type === 'online',
                'tone'   => $a->type === 'session' ? 'sage' : 'lavender',
            ]);

        // ── Richieste da gestire ────────────────────────────────────────────
        $contactPending = ContactRequest::where('professional_id', $user->id)->where('status', 'pending')->count();
        $enrollPending = GroupEnrollmentRequest::whereIn('status', ['da_contattare', 'in_attesa_conferma'])->count();

        $requests = [
            ['label' => 'Primi contatti',      'sub' => 'Da ricontattare', 'count' => $contactPending, 'tone' => 'lavender', 'href' => route('contact-requests.inbox')],
            ['label' => 'Iscrizioni ai gruppi', 'sub' => 'Da confermare',   'count' => $enrollPending,  'tone' => 'sage',     'href' => route('groups.index')],
        ];
        $requestsTotal = $contactPending + $enrollPending;

        // ── Attività da completare ──────────────────────────────────────────
        $tasks = Task::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id'    => $t->id,
                'title' => $t->title,
                'due'   => $this->dueLabel($t->due_date),
            ]);

        // ── Gruppi attivi ───────────────────────────────────────────────────
        $groups = Group::withCount('participants')
            ->whereIn('status', ['attivo', 'in_partenza'])
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(fn (Group $g) => [
                'id'       => $g->id,
                'name'     => $g->name,
                'tone'     => $g->categoryConfig()['tone'],
                'enrolled' => $g->participants_count,
                'capacity' => $g->capacity,
            ]);

        return Inertia::render('Dashboard', [
            'userName'          => $user->name,
            'stats'             => $stats,
            'todayAppointments' => $todayAppointments,
            'requests'          => $requests,
            'requestsTotal'     => $requestsTotal,
            'tasks'             => $tasks,
            'groups'            => $groups,
        ]);
    }

    private function dueLabel($date): string
    {
        if (! $date) {
            return '';
        }
        if ($date->isToday()) {
            return 'Oggi';
        }
        if ($date->isTomorrow()) {
            return 'Domani';
        }
        if ($date->lessThan(now())) {
            return 'Scaduto';
        }

        return $date->locale('it')->isoFormat('ddd D');
    }
}
