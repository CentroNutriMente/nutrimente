<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\ProfessionalProfile;
use App\Models\User;
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
            ->whereHas('roles', fn ($q) => $q->where('name', '!=', 'admin'))
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
}
