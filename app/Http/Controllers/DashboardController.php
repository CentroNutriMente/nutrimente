<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Questionnaire;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $myPatientIds = Patient::where('created_by', $user->id)
            ->orWhereHas('professionals', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $stats = [
            'appointments_today' => Appointment::whereDate('start_at', today())
                ->where('user_id', $user->id)
                ->count(),
            'active_patients' => Patient::where('is_active', true)
                ->where(fn ($q) => $q
                    ->where('created_by', $user->id)
                    ->orWhereHas('professionals', fn ($q2) => $q2->where('user_id', $user->id))
                )->count(),
            'invoices_month' => Invoice::where('user_id', $user->id)
                ->whereMonth('issued_at', now()->month)
                ->whereYear('issued_at', now()->year)
                ->whereNotIn('status', ['cancelled'])
                ->count(),
            'revenue_month' => Invoice::where('user_id', $user->id)
                ->whereMonth('issued_at', now()->month)
                ->whereYear('issued_at', now()->year)
                ->whereNotIn('status', ['cancelled'])
                ->sum('total'),
        ];

        $todayAppointments = Appointment::with('patient')
            ->where('user_id', $user->id)
            ->whereDate('start_at', today())
            ->orderBy('start_at')
            ->get();

        // Recent activity: last 7 days of reports + questionnaires authored by this professional
        $recentReports = Report::with('patient')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'type'  => 'report',
                'label' => $r->patient ? "{$r->patient->first_name} {$r->patient->last_name}" : $r->title,
                'sub'   => 'Referto',
                'at'    => $r->created_at->toISOString(),
            ]);

        $recentQuestionnaires = Questionnaire::with(['patient', 'template'])
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($q) => [
                'type'  => 'questionnaire',
                'label' => $q->patient ? "{$q->patient->first_name} {$q->patient->last_name}" : '—',
                'sub'   => $q->template?->name ?? 'Questionario',
                'at'    => $q->created_at->toISOString(),
            ]);

        $recentActivity = $recentReports->concat($recentQuestionnaires)
            ->sortByDesc('at')
            ->values()
            ->take(5);

        return Inertia::render('Dashboard', [
            'stats'             => $stats,
            'todayAppointments' => $todayAppointments,
            'recentActivity'    => $recentActivity,
            'userName'          => $user->name,
        ]);
    }
}
