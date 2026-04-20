<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingRequestMail;
use App\Models\Appointment;
use App\Models\BookingRequest;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    // ── Public landing: all bookable professionals ────────────────────────────

    public function index(): Response
    {
        $professionals = User::with(['professionalProfile', 'roles'])
            ->whereHas('roles', fn ($q) => $q->where('name', '!=', 'admin'))
            ->whereHas('professionalProfile', fn ($q) => $q->where('is_bookable', true))
            ->get()
            ->map(fn ($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'slug'     => $u->professionalProfile?->slug,
                'role'     => $u->getRoleNames()->first(),
                'category' => $u->professionalProfile?->category,
                'title'    => $u->professionalProfile?->title,
                'bio'      => $u->professionalProfile?->bio
                    ? Str::limit($u->professionalProfile->bio, 160)
                    : null,
                'photo'    => $u->profile_photo_url,
            ]);

        return Inertia::render('Booking/Index', compact('professionals'));
    }

    // ── Professional public profile + weekly calendar ─────────────────────────

    public function show(string $slug): Response
    {
        $profile = ProfessionalProfile::where('slug', $slug)
            ->where('is_bookable', true)
            ->with('user.availabilitySlots')
            ->firstOrFail();

        $user = $profile->user;

        // Collect already-confirmed appointment date+time combos for the next 8 weeks
        $bookedSlots = Appointment::where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'scheduled'])
            ->where('start_at', '>=', now()->startOfWeek())
            ->where('start_at', '<=', now()->addWeeks(8)->endOfWeek())
            ->get(['start_at'])
            ->map(fn ($a) => [
                'date' => Carbon::parse($a->start_at)->format('Y-m-d'),
                'time' => Carbon::parse($a->start_at)->format('H:i'),
            ])
            ->toArray();

        $availability = $user->availabilitySlots()
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get(['day_of_week', 'start_time', 'end_time'])
            ->map(fn ($s) => [
                'day_of_week' => $s->day_of_week,
                'start_time'  => substr($s->start_time, 0, 5),
                'end_time'    => substr($s->end_time, 0, 5),
            ]);

        return Inertia::render('Booking/Show', [
            'professional' => [
                'id'               => $user->id,
                'name'             => $user->name,
                'slug'             => $slug,
                'role'             => $user->getRoleNames()->first(),
                'category'         => $profile->category,
                'title'            => $profile->title,
                'bio'              => $profile->bio,
                'curriculum'       => json_decode($profile->curriculum, true) ?? $profile->curriculum,
                'photo'            => $user->profile_photo_url,
                'session_duration' => $profile->session_duration_minutes,
                'session_price'    => $profile->session_price,
            ],
            'availability'  => $availability,
            'booked_slots'  => $bookedSlots,
        ]);
    }

    // ── Store booking request ─────────────────────────────────────────────────

    public function store(Request $request, string $slug): RedirectResponse
    {
        $profile = ProfessionalProfile::where('slug', $slug)
            ->where('is_bookable', true)
            ->with('user')
            ->firstOrFail();

        $validated = $request->validate([
            'patient_name'    => 'required|string|max:100',
            'patient_surname' => 'required|string|max:100',
            'patient_email'   => 'required|email|max:255',
            'patient_phone'   => 'nullable|string|max:20',
            'notes'           => 'nullable|string|max:1000',
            'requested_date'  => 'required|date|after_or_equal:today',
            'requested_time'  => 'required|date_format:H:i',
        ]);

        $booking = BookingRequest::create([
            ...$validated,
            'professional_id' => $profile->user_id,
            'status'          => 'pending',
            'confirm_token'   => Str::random(48),
            'reject_token'    => Str::random(48),
            'invite_token'    => Str::random(48),
        ]);

        try {
            Mail::to($profile->user->email)->send(new BookingRequestMail($booking->load('professional.professionalProfile')));
        } catch (\Exception $e) {
            \Log::error('BookingRequest mail failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Richiesta inviata! Il professionista ti contatterà a breve.');
    }

    // ── Professional confirms via email link ──────────────────────────────────

    public function confirm(string $slug, string $token): Response|RedirectResponse
    {
        $booking = BookingRequest::where('confirm_token', $token)
            ->where('status', 'pending')
            ->with('professional.professionalProfile')
            ->firstOrFail();

        // Create the actual appointment in the system
        $startAt  = Carbon::parse("{$booking->requested_date} {$booking->requested_time}");
        $duration = $booking->professional->professionalProfile?->session_duration_minutes ?? 50;

        // Try to find an existing patient record by email to link it
        $patient = \App\Models\Patient::where('email', $booking->patient_email)->first();

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
            'confirm_token'  => null, // invalidate
        ]);

        // Send confirmation email to patient
        try {
            Mail::to($booking->patient_email)->send(new BookingConfirmedMail($booking));
        } catch (\Exception $e) {
            \Log::error('BookingConfirmed mail failed: ' . $e->getMessage());
        }

        return Inertia::render('Booking/Confirmed', [
            'booking' => [
                'patient_name'    => $booking->patient_name,
                'patient_surname' => $booking->patient_surname,
                'patient_email'   => $booking->patient_email,
                'requested_date'  => $booking->requested_date->format('Y-m-d'),
                'requested_time'  => substr($booking->requested_time, 0, 5),
                'professional'    => $booking->professional->name,
            ],
        ]);
    }

    // ── Professional rejects via email link ───────────────────────────────────

    public function reject(string $slug, string $token): Response|RedirectResponse
    {
        $booking = BookingRequest::where('reject_token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $booking->update([
            'status'       => 'rejected',
            'reject_token' => null,
        ]);

        return Inertia::render('Booking/Rejected', [
            'patient_name' => $booking->patient_name,
        ]);
    }
}
