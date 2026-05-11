<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentCancelledByPatientMail;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
}
