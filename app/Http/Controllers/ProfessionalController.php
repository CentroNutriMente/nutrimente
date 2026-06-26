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
    /** La creazione di nuovi account professionista è riservata agli admin. */
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, 'Area riservata agli amministratori.');
    }

    /** Un profilo può essere visto/modificato solo dall'admin o dal diretto interessato. */
    private function ensureAdminOrSelf(User $user): void
    {
        $current = auth()->user();
        abort_unless($current->hasRole('admin') || $current->id === $user->id, 403);
    }

    public function index(): Response
    {
        $current = auth()->user();
        $isAdmin = $current->hasRole('admin');

        $professionals = User::with('professionalProfile', 'roles')
            ->whereHas('professionalProfile')
            // I non-admin vedono e gestiscono solo il proprio profilo.
            ->when(! $isAdmin, fn ($q) => $q->whereKey($current->id))
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
            'isAdmin'       => $isAdmin,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

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

        // Always create a profile with a slug
        $base = Str::slug($user->name);
        $slug = $base; $i = 2;
        while (\App\Models\ProfessionalProfile::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $user->professionalProfile()->create([
            'category' => $validated['category'] ?? null,
            'slug'     => $slug,
        ]);

        return redirect()->route('professionals.index')->with('success', 'Professionista aggiunto.');
    }

    public function show(User $user): Response
    {
        $this->ensureAdminOrSelf($user);

        $user->load('professionalProfile', 'roles', 'availabilitySlots');

        return Inertia::render('Professionals/Show', [
            'professional' => $user,
            'slug'         => $user->professionalProfile?->slug,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdminOrSelf($user);

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
        $this->ensureAdminOrSelf($user);

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
        $this->ensureAdminOrSelf($user);
        abort_if($slot->user_id !== $user->id, 403);
        $slot->delete();
        return back()->with('success', 'Fascia rimossa.');
    }
}
