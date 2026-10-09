<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmedMail;
use App\Models\Appointment;
use App\Models\BookingRequest;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentRequestController extends Controller
{
    /** Elenco delle richieste di appuntamento in attesa per il professionista loggato. */
    public function index(): Response
    {
        $requests = BookingRequest::where('professional_id', auth()->id())
            ->where('status', 'pending')
            ->orderBy('requested_date')
            ->orderBy('requested_time')
            ->get()
            ->map(fn (BookingRequest $b) => [
                'id'             => $b->id,
                'name'           => $b->fullName(),
                'email'          => $b->patient_email,
                'phone'          => $b->patient_phone,
                'notes'          => $b->notes,
                'requested_date' => $b->requested_date->format('d/m/Y'),
                'requested_time' => substr((string) $b->requested_time, 0, 5),
                'created_at'     => $b->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Appointments/Requests', [
            'requests' => $requests,
        ]);
    }

    /** Conferma: crea l'appuntamento e avvisa il paziente (stessa logica del link email). */
    public function confirm(BookingRequest $booking): RedirectResponse
    {
        abort_unless($booking->professional_id === auth()->id(), 403);
        abort_unless($booking->status === 'pending', 422);

        $booking->loadMissing('professional.professionalProfile');

        $startAt  = Carbon::parse("{$booking->requested_date->format('Y-m-d')} {$booking->requested_time}");
        $duration = $booking->professional->professionalProfile?->session_duration_minutes ?? 50;
        $patient  = Patient::whereBlind('email', $booking->patient_email)->first();

        $appointment = Appointment::create([
            'user_id'     => $booking->professional_id,
            'patient_id'  => $patient?->id,
            'title'       => "Appuntamento – {$booking->fullName()}",
            'description' => $booking->notes,
            'start_at'    => $startAt,
            'end_at'      => $startAt->copy()->addMinutes($duration),
            'type'        => 'session',
            'status'      => 'confirmed',
            'color'       => '#10b981',
        ]);

        $booking->update([
            'status'         => 'confirmed',
            'confirmed_at'   => now(),
            'appointment_id' => $appointment->id,
            'confirm_token'  => null,
            'reject_token'   => null,
        ]);

        try {
            Mail::to($booking->patient_email)->send(new BookingConfirmedMail($booking));
        } catch (\Exception $e) {
            \Log::error('BookingConfirmed (portal) mail failed: ' . $e->getMessage());
        }

        return redirect()->route('appointment-requests.index')
            ->with('flash', ['banner' => 'Appuntamento confermato e inserito in agenda.']);
    }

    /** Rifiuta la richiesta di appuntamento. */
    public function reject(BookingRequest $booking): RedirectResponse
    {
        abort_unless($booking->professional_id === auth()->id(), 403);
        abort_unless($booking->status === 'pending', 422);

        $booking->update([
            'status'        => 'rejected',
            'confirm_token' => null,
            'reject_token'  => null,
        ]);

        return redirect()->route('appointment-requests.index')
            ->with('flash', ['banner' => 'Richiesta di appuntamento rifiutata.']);
    }
}
