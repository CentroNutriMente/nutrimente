<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::with(['patient', 'user'])
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->start && $request->end, fn ($q) => $q
                ->where('start_at', '<', $request->end)
                ->where('end_at', '>', $request->start)
            )
            ->get()
            ->map(fn ($apt) => [
                'id' => $apt->id,
                'title' => $apt->patient
                    ? "{$apt->patient->last_name} {$apt->patient->first_name}"
                    : $apt->title,
                'start' => $apt->start_at,
                'end' => $apt->end_at,
                'color' => $apt->color ?? $this->colorForType($apt->type),
                'extendedProps' => [
                    'type' => $apt->type,
                    'status' => $apt->status,
                    'room' => $apt->room,
                    'professional' => $apt->user?->name,
                    'patient_id' => $apt->patient_id,
                ],
            ]);

        return response()->json($appointments);
    }

    public function calendar(Request $request): Response
    {
        $professionals = User::with('professionalProfile')
            ->whereHas('roles')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('Calendar/Index', [
            'professionals' => $professionals,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Calendar/AppointmentForm', [
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'professionals' => User::with('professionalProfile')->get(['id', 'name']),
            'prefill' => $request->only(['patient_id', 'start_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'patient_id' => 'nullable|exists:patients,id',
            'type' => 'required|in:session,intervision,personal,blocked',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'room' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
            'is_shared' => 'boolean',
        ]);

        $appointment = Appointment::create($validated);

        return redirect()->route('calendar')->with('success', 'Appuntamento creato.');
    }

    public function show(Appointment $appointment): Response
    {
        return Inertia::render('Calendar/AppointmentShow', [
            'appointment' => $appointment->load(['patient', 'user', 'intervisione']),
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        return Inertia::render('Calendar/AppointmentForm', [
            'appointment' => $appointment,
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'professionals' => User::with('professionalProfile')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'start_at' => 'sometimes|required|date',
            'end_at' => 'sometimes|required|date|after:start_at',
            'status' => 'sometimes|in:scheduled,confirmed,cancelled,completed',
            'room' => 'nullable|string',
            'cancellation_reason' => 'nullable|string',
        ]);

        $appointment->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('calendar')->with('success', 'Appuntamento aggiornato.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();
        return redirect()->route('calendar')->with('success', 'Appuntamento eliminato.');
    }

    private function colorForType(string $type): string
    {
        return match ($type) {
            'session' => '#9333ea',   // purple-600
            'intervision' => '#c084fc', // purple-400
            'personal' => '#a78bfa',   // violet-400
            'blocked' => '#d1d5db',    // gray-300
            default => '#9333ea',
        };
    }
}
