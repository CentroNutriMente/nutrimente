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
            'total_patients' => Patient::count(),
            'appointments_today' => Appointment::whereDate('start_at', today())
                ->where('user_id', $user->id)
                ->count(),
            'appointments_week' => Appointment::whereBetween('start_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('user_id', $user->id)
                ->count(),
            'invoices_unpaid' => Invoice::where('user_id', $user->id)
                ->where('status', 'issued')
                ->whereNull('paid_at')
                ->count(),
            'revenue_month' => Invoice::where('user_id', $user->id)
                ->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('total'),
        ];

        $upcomingAppointments = Appointment::with('patient')
            ->where('user_id', $user->id)
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'upcomingAppointments' => $upcomingAppointments,
        ]);
    }
}
