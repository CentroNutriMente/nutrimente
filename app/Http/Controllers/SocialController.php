<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocialController extends Controller
{
    public function index(): Response
    {
        $posts = SocialPost::with('creator')
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'category'     => $p->category,
                'content'      => $p->content,
                'platforms'    => $p->platforms ?? [],
                'status'       => $p->status,
                'scheduled_at' => $p->scheduled_at?->format('Y-m-d'),
                'notes'        => $p->notes,
                'created_by'   => $p->creator->name,
            ]);

        return Inertia::render('Social/Index', [
            'posts' => $posts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:50',
            'content'      => 'nullable|string',
            'platforms'    => 'nullable|array',
            'platforms.*'  => 'string',
            'status'       => 'in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        SocialPost::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return back();
    }

    public function update(Request $request, SocialPost $socialPost): RedirectResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:50',
            'content'      => 'nullable|string',
            'platforms'    => 'nullable|array',
            'platforms.*'  => 'string',
            'status'       => 'in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        $socialPost->update($validated);

        return back();
    }

    public function destroy(SocialPost $socialPost): RedirectResponse
    {
        $socialPost->delete();
        return back();
    }
}
