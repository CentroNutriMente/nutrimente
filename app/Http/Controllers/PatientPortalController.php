<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentCancelledByPatientMail;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PatientPortalController extends Controller
{
    public function dashboard(): Response
    {
        $user = auth()->user();

        // Find the patient record linked by email
        $patient = Patient::where('email', $user->email)
            ->with([
                'appointments' => fn ($q) => $q->with('user')->orderByDesc('start_at'),
                'reports'      => fn ($q) => $q->with(['user', 'template'])->orderByDesc('report_date'),
                'invoices'     => fn ($q) => $q->orderByDesc('issued_at'),
            ])
            ->first();

        // List of bookable professionals for "new appointment" CTA
        $professionals = User::with(['professionalProfile', 'roles'])
            ->whereHas('professionalProfile', fn ($q) => $q->where('is_bookable', true))
            ->get()
            ->map(fn ($u) => [
                'name'  => $u->name,
                'slug'  => $u->professionalProfile?->slug,
                'title' => $u->professionalProfile?->title,
                'role'  => $u->getRoleNames()->first(),
                'photo' => $u->profile_photo_url,
            ]);

        return Inertia::render('PatientPortal/Dashboard', [
            'patient'       => $patient,
            'professionals' => $professionals,
        ]);
    }

    public function cancelAppointment(Appointment $appointment): RedirectResponse
    {
        $user = auth()->user();

        // Verify the appointment belongs to this patient (matched by email)
        $patient = Patient::where('email', $user->email)->first();
        abort_if(! $patient || $appointment->patient_id !== $patient->id, 403);
        abort_if($appointment->start_at->isPast(), 422, 'Non puoi disdire un appuntamento già passato.');

        $appointment->load('user', 'patient');
        $patientName = "{$patient->first_name} {$patient->last_name}";

        $appointment->update(['status' => 'cancelled']);

        // Notify the professional
        try {
            Mail::to($appointment->user->email)
                ->send(new AppointmentCancelledByPatientMail($appointment, $patientName));
        } catch (\Exception $e) {
            \Log::error('AppointmentCancelledByPatientMail failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Appuntamento disdetto.');
    }
}
