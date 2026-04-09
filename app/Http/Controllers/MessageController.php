<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Team channels: one per professional category + general
        $teamChannels = [
            ['id' => 'general', 'label' => 'Generale', 'icon' => 'hash'],
            ['id' => 'psicologo', 'label' => 'Psicologi', 'icon' => 'hash'],
            ['id' => 'nutrizionista', 'label' => 'Nutrizionisti', 'icon' => 'hash'],
            ['id' => 'osteopata', 'label' => 'Osteopati', 'icon' => 'hash'],
        ];

        $colleagues = User::with('professionalProfile', 'roles')
            ->whereHas('roles')
            ->where('id', '!=', $user->id)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'profile_photo_url' => $u->profile_photo_url,
                'role' => $u->getRoleNames()->first(),
            ]);

        return Inertia::render('Messages/Index', [
            'teamChannels' => $teamChannels,
            'colleagues' => $colleagues,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'profile_photo_url' => $user->profile_photo_url,
            ],
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $request->validate([
            'channel_type' => 'required|in:team,direct',
            'channel_id' => 'required|string',
        ]);

        $messages = Message::with('sender')
            ->where('channel_type', $request->channel_type)
            ->where('channel_id', $request->channel_id)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender->name,
                'sender_photo' => $m->sender->profile_photo_url,
                'created_at' => $m->created_at,
                'attachment_name' => $m->attachment_name,
                'attachment_path' => $m->attachment_path,
            ]);

        return response()->json($messages);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_type' => 'required|in:team,direct',
            'channel_id' => 'required|string',
            'body' => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'channel_type' => $validated['channel_type'],
            'channel_id' => $validated['channel_id'],
            'body' => $validated['body'],
        ]);

        $message->load('sender');

        return response()->json([
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'sender_photo' => $message->sender->profile_photo_url,
            'created_at' => $message->created_at,
        ], 201);
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        if ($message->sender_id !== $request->user()->id) {
            abort(403);
        }
        $message->update(['deleted_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
