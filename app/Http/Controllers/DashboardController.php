<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stats = [
            'appointments_today' => Appointment::whereDate('start_at', today())
                ->where('user_id', $user->id)
                ->count(),
            'active_patients' => Patient::where('is_active', true)->count(),
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

        $recentActivity = Appointment::with('patient')
            ->where('user_id', $user->id)
            ->whereDate('start_at', today())
            ->orderByDesc('start_at')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'todayAppointments' => $todayAppointments,
            'recentActivity' => $recentActivity,
            'userName' => $user->name,
        ]);
    }
}
