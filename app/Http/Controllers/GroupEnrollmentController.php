<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupEnrollmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GroupEnrollmentController extends Controller
{
    // Form pubblico generico: l'utente sceglie il gruppo d'interesse.
    public function create(): Response
    {
        return Inertia::render('Booking/GroupEnrollment', [
            'groups'     => $this->openGroups(),
            'categories' => $this->categories(),
            'preselect'  => null,
            'source'     => 'form',
        ]);
    }

    // Form pre-compilato dal QR del volantino (token del gruppo).
    public function show(string $token): Response
    {
        $group = Group::where('public_token', $token)->firstOrFail();

        return Inertia::render('Booking/GroupEnrollment', [
            'groups'     => $this->openGroups(),
            'categories' => $this->categories(),
            'preselect'  => ['id' => $group->id, 'name' => $group->name, 'category' => $group->category],
            'source'     => 'qr',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'email'           => ['required', 'email', 'max:160'],
            'phone'           => ['required', 'string', 'max:40'],
            'codice_fiscale'  => ['required', 'string', 'regex:/^[A-Za-z0-9]{16}$/'],
            'group_id'        => ['nullable', 'exists:support_groups,id'],
            'how_heard'       => ['nullable', 'string', 'max:120'],
            'privacy_consent' => ['accepted'],
            'source'          => ['nullable', Rule::in(['form', 'qr'])],
        ]);

        $category = null;
        if (! empty($validated['group_id'])) {
            $category = Group::find($validated['group_id'])?->category;
        }

        GroupEnrollmentRequest::create([
            'group_id'        => $validated['group_id'] ?? null,
            'category'        => $category,
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'],
            'codice_fiscale'  => strtoupper($validated['codice_fiscale']),
            'how_heard'       => $validated['how_heard'] ?? null,
            'privacy_consent' => true,
            'source'          => $validated['source'] ?? 'form',
            'status'          => 'da_contattare',
        ]);

        return back()->with('flash', ['banner' => 'Richiesta inviata! Ti ricontatteremo a breve.']);
    }

    private function openGroups(): array
    {
        return Group::whereIn('status', ['attivo', 'in_partenza'])
            ->orderBy('name')
            ->get(['id', 'name', 'category'])
            ->map(fn ($g) => [
                'id'       => $g->id,
                'name'     => $g->name,
                'category' => $g->category,
            ])->all();
    }

    private function categories(): array
    {
        return collect(config('groups.categories'))
            ->map(fn ($c, $key) => array_merge($c, ['key' => $key]))
            ->values()->all();
    }
}
