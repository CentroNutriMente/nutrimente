<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalController extends Controller
{
    public function index(): Response
    {
        $professionals = User::with('professionalProfile', 'roles')
            ->whereHas('roles')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'profile_photo_url' => $u->profile_photo_url,
                'role' => $u->getRoleNames()->first(),
                'category' => $u->professionalProfile?->category,
                'title' => $u->professionalProfile?->title,
                'is_bookable' => $u->professionalProfile?->is_bookable ?? false,
            ]);

        return Inertia::render('Professionals/Index', [
            'professionals' => $professionals,
        ]);
    }

    public function show(User $user): Response
    {
        return Inertia::render('Professionals/Show', [
            'professional' => $user->load('professionalProfile', 'roles'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:50',
            'bio' => 'nullable|string',
            'curriculum' => 'nullable|string',
            'partita_iva' => 'nullable|string|max:20',
            'codice_fiscale' => 'nullable|string|max:16',
            'regime_fiscale' => 'nullable|string',
            'cassa_previdenziale' => 'nullable|string',
            'albo_professionale' => 'nullable|string',
            'numero_albo' => 'nullable|string',
            'is_bookable' => 'boolean',
            'session_duration_minutes' => 'nullable|integer|min:15',
            'session_price' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
        ]);

        $user->professionalProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return back()->with('success', 'Profilo aggiornato.');
    }
}
