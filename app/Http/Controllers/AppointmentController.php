<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentCancelledByProfessionalMail;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $accessiblePatientIds = array_flip(
                Patient::where('created_by', $userId)
                    ->orWhereHas('professionals', fn ($q) => $q->where('user_id', $userId))
                    ->pluck('id')
                    ->all()
            );

            $filterUserId = $request->input('user_id');

            $appointments = Appointment::with(['patient', 'user'])
                ->when($filterUserId, fn ($q) => $q->where('user_id', $filterUserId))
                ->when($request->input('start') && $request->input('end'), fn ($q) => $q
                    ->where('start_at', '<', $request->input('end'))
                    ->where('end_at', '>', $request->input('start'))
                )
                ->get()
                ->map(function ($apt) use ($userId, $accessiblePatientIds) {
                    $isPrivate = $apt->patient_id && ! isset($accessiblePatientIds[$apt->patient_id]);

                    return [
                        'id'       => $apt->id,
                        'title'    => $isPrivate
                            ? 'Occupato'
                            : ($apt->patient ? "{$apt->patient->last_name} {$apt->patient->first_name}" : $apt->title),
                        'start'    => $apt->start_at,
                        'end'      => $apt->end_at,
                        'color'    => $isPrivate ? '#9ca3af' : ($apt->color ?? $this->colorForType($apt->type)),
                        'editable' => ! $isPrivate && $apt->user_id === $userId,
                        'extendedProps' => [
                            'type'         => $apt->type,
                            'status'       => $isPrivate ? null : $apt->status,
                            'room'         => $isPrivate ? null : $apt->room,
                            'professional' => $apt->user?->name,
                            'patient_id'   => $isPrivate ? null : $apt->patient_id,
                            'is_private'   => $isPrivate,
                            'is_own'       => $apt->user_id === $userId,
                        ],
                    ];
                });

            return response()->json($appointments);

        } catch (\Throwable $e) {
            \Log::error('AppointmentController@index failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user' => $request->user()?->id,
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calendar(Request $request): Response
    {
        $professionals = User::whereHas('professionalProfile')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('Calendar/Index', [
            'professionals' => $professionals,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Calendar/AppointmentForm', [
            'patients'      => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'professionals' => User::whereHas('professionalProfile')->orderBy('name')->get(['id', 'name']),
            'prefill'       => $request->only(['patient_id', 'start_at']),
            'authUserId'    => $request->user()->id,
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

        if (! empty($validated['patient_id'])) {
            $patient = Patient::find($validated['patient_id']);
            if ($patient && $patient->created_by !== null && $patient->created_by !== $request->user()->id) {
                abort(403, 'Solo il creatore del paziente può creare appuntamenti per questo paziente.');
            }
        }

        $appointment = Appointment::create($validated);

        if ($request->input('return_to') === 'patient' && $appointment->patient_id) {
            return redirect()->route('patients.show', $appointment->patient_id)
                ->with('success', 'Appuntamento creato.');
        }

        return redirect()->route('calendar')->with('success', 'Appuntamento creato.');
    }

    public function show(Appointment $appointment): Response
    {
        return Inertia::render('Calendar/AppointmentShow', [
            'appointment' => $appointment->load(['patient', 'user', 'intervisione']),
        ]);
    }

    public function edit(Request $request, Appointment $appointment): Response
    {
        return Inertia::render('Calendar/AppointmentForm', [
            'appointment'   => $appointment,
            'patients'      => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'professionals' => User::whereHas('professionalProfile')->orderBy('name')->get(['id', 'name']),
            'authUserId'    => $request->user()->id,
            'returnTo'      => $request->input('return_to'),
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'sometimes|required|string|max:255',
            'start_at'            => 'sometimes|required|date',
            'end_at'              => 'sometimes|required|date|after:start_at',
            'status'              => 'sometimes|in:scheduled,confirmed,cancelled,completed',
            'room'                => 'nullable|string',
            'cancellation_reason' => 'nullable|string',
            'return_to'           => 'nullable|string',
        ]);

        unset($validated['return_to']);

        $wasCancelled = $appointment->status !== 'cancelled'
            && ($validated['status'] ?? null) === 'cancelled';

        $appointment->load('user', 'patient');
        $appointment->update($validated);

        if ($wasCancelled) {
            $this->notifyPatientCancelled($appointment);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        if ($request->input('return_to') === 'patient' && $appointment->patient_id) {
            return redirect()->route('patients.show', $appointment->patient_id)
                ->with('success', 'Appuntamento aggiornato.');
        }

        return redirect()->route('calendar')->with('success', 'Appuntamento aggiornato.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->load('user', 'patient');
        $this->notifyPatientCancelled($appointment);
        $appointment->delete();
        return redirect()->route('calendar')->with('success', 'Appuntamento eliminato.');
    }

    private function notifyPatientCancelled(Appointment $appointment): void
    {
        $email = $appointment->patient?->email;
        if (! $email) return;

        try {
            Mail::to($email)->send(new AppointmentCancelledByProfessionalMail($appointment));
        } catch (\Exception $e) {
            \Log::error('AppointmentCancelledByProfessionalMail failed: ' . $e->getMessage());
        }
    }

    private function colorForType(string $type): string
    {
        return match ($type) {
            'session'    => '#7c3aed',   // viola scuro  – seduta
            'intervision'=> '#0ea5e9',   // azzurro       – intervisione
            'personal'   => '#10b981',   // verde         – personale
            'blocked'    => '#9ca3af',   // grigio        – bloccato
            default      => '#7c3aed',
        };
    }
}
