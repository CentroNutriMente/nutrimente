<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentCancelledByPatientMail;
use App\Mail\BookingRequestMail;
use App\Models\Appointment;
use App\Models\BookingRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PatientPortalController extends Controller
{
    private const GDPR_DOCS = [
        'consenso-informato' => 'Consenso Informato.pdf',
        'privacy-policy'     => 'Privacy Policy.pdf',
    ];

    public function dashboard(): Response
    {
        $user = auth()->user();

        $patient = Patient::where('email', $user->email)
            ->with([
                'appointments'     => fn ($q) => $q->with('user')->orderBy('start_at'),
                'invoices'         => fn ($q) => $q->orderByDesc('issued_at'),
                'consents',
                'consentDocuments' => fn ($q) => $q->whereNull('deleted_at')->orderByDesc('created_at'),
            ])
            ->first();

        // Resolve the booking slug for the primary professional (creator)
        $bookingSlug = null;
        if ($patient?->created_by) {
            $creator = User::with('professionalProfile')->find($patient->created_by);
            $bookingSlug = $creator?->professionalProfile?->slug;
        }

        // Which GDPR documents are physically available on disk
        $gdprAvailable = [];
        foreach (self::GDPR_DOCS as $slug => $filename) {
            if (Storage::disk('local')->exists("gdpr/{$filename}")) {
                $gdprAvailable[] = ['slug' => $slug, 'label' => pathinfo($filename, PATHINFO_FILENAME)];
            }
        }

        return Inertia::render('PatientPortal/Dashboard', [
            'patient'        => $patient,
            'gdprAvailable'  => $gdprAvailable,
            'bookingSlug'    => $bookingSlug,
        ]);
    }

    public function cancelAppointment(Appointment $appointment): RedirectResponse
    {
        $user = auth()->user();

        $patient = Patient::where('email', $user->email)->first();
        abort_if(! $patient || $appointment->patient_id !== $patient->id, 403);
        abort_if($appointment->start_at->isPast(), 422, 'Non puoi disdire un appuntamento già passato.');

        $appointment->load('user', 'patient');
        $patientName = "{$patient->first_name} {$patient->last_name}";

        $appointment->update(['status' => 'cancelled']);

        try {
            Mail::to($appointment->user->email)
                ->send(new AppointmentCancelledByPatientMail($appointment, $patientName));
        } catch (\Exception $e) {
            \Log::error('AppointmentCancelledByPatientMail failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Appuntamento disdetto.');
    }

    public function requestAppointment(): RedirectResponse
    {
        $user = auth()->user();

        $patient = Patient::where('email', $user->email)->first();
        abort_if(! $patient || ! $patient->created_by, 422, 'Nessun professionista associato.');

        $creator = User::with('professionalProfile')->find($patient->created_by);
        abort_if(! $creator || ! $creator->professionalProfile, 422, 'Professionista non trovato.');

        $validated = request()->validate([
            'requested_date' => 'required|date|after_or_equal:today',
            'requested_time' => 'required|date_format:H:i',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $booking = BookingRequest::create([
            'professional_id'  => $creator->id,
            'patient_name'     => $patient->first_name,
            'patient_surname'  => $patient->last_name,
            'patient_email'    => $patient->email,
            'patient_phone'    => $patient->phone,
            'notes'            => $validated['notes'] ?? null,
            'requested_date'   => $validated['requested_date'],
            'requested_time'   => $validated['requested_time'],
            'status'           => 'pending',
            'confirm_token'    => Str::random(48),
            'reject_token'     => Str::random(48),
            'invite_token'     => Str::random(48),
        ]);

        try {
            Mail::to($creator->email)->send(new BookingRequestMail($booking->load('professional.professionalProfile')));
        } catch (\Exception $e) {
            \Log::error('BookingRequest (portal) mail failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Richiesta inviata! Il tuo professionista ti contatterà a breve per confermare.');
    }
}
