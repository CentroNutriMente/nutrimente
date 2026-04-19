<?php

namespace App\Http\Controllers;

use App\Models\AvailabilitySlot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ProfessionalController extends Controller
{
    public function index(): Response
    {
        $professionals = User::with('professionalProfile', 'roles')
            ->whereHas('roles', fn ($q) => $q->where('name', '!=', 'admin'))
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:psicologo,nutrizionista,osteopata,collaboratore',
            'category' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $role = Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
        $user->assignRole($role);

        if ($validated['category'] ?? null) {
            $user->professionalProfile()->create([
                'category' => $validated['category'],
            ]);
        }

        return redirect()->route('professionals.index')->with('success', 'Professionista aggiunto.');
    }

    public function show(User $user): Response
    {
        $user->load('professionalProfile', 'roles', 'availabilitySlots');

        return Inertia::render('Professionals/Show', [
            'professional' => $user,
            'slug'         => $user->professionalProfile?->slug,
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

        // Auto-generate slug if not yet set
        if (! $user->professionalProfile?->slug) {
            $base = Str::slug($user->name);
            $slug = $base; $i = 2;
            while (\App\Models\ProfessionalProfile::where('slug', $slug)->where('user_id', '!=', $user->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $validated['slug'] = $slug;
        }

        $user->professionalProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return back()->with('success', 'Profilo aggiornato.');
    }

    // ── Availability slots ────────────────────────────────────────────────────

    public function storeSlot(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'room'        => 'nullable|string|max:100',
        ]);

        $user->availabilitySlots()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', 'Fascia oraria aggiunta.');
    }

    public function destroySlot(User $user, AvailabilitySlot $slot): RedirectResponse
    {
        abort_if($slot->user_id !== $user->id, 403);
        $slot->delete();
        return back()->with('success', 'Fascia rimossa.');
    }
}
