<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

        // Per-channel unread count (from notifications)
        $unreadByChannel = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->where('type', 'message')
            ->whereNotNull('data')
            ->get()
            ->groupBy(fn ($n) => $n->data['channel_id'] ?? null)
            ->filter(fn ($g, $k) => $k !== null)
            ->map->count();

        return Inertia::render('Messages/Index', [
            'teamChannels'   => $teamChannels,
            'colleagues'     => $colleagues,
            'unreadByChannel' => $unreadByChannel,
            'currentUser' => [
                'id'                => $user->id,
                'name'              => $user->name,
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

        $sender     = $request->user();
        $senderId   = $sender->id;
        $senderName = $sender->name;
        $preview    = mb_strimwidth($validated['body'], 0, 80, '…');

        if ($validated['channel_type'] === 'direct') {
            // channel_id format: dm_{minId}_{maxId}
            $parts       = explode('_', $validated['channel_id']); // ['dm','1','2']
            $recipientId = collect([(int)($parts[1] ?? 0), (int)($parts[2] ?? 0)])
                ->first(fn ($id) => $id !== $senderId);
            if ($recipientId && $recipientId !== $senderId) {
                Notification::send(
                    $recipientId,
                    'message',
                    "Messaggio diretto da {$senderName}",
                    $preview,
                    ['channel_type' => 'direct', 'channel_id' => $validated['channel_id']]
                );
            }
        } else {
            // Team channel: notify all professionals except sender
            $channelId = $validated['channel_id'];
            User::whereHas('roles')
                ->where('id', '!=', $senderId)
                ->pluck('id')
                ->each(fn ($uid) => Notification::send(
                    $uid,
                    'message',
                    "#{$channelId} — {$senderName}",
                    $preview,
                    ['channel_type' => 'team', 'channel_id' => $channelId]
                ));
        }

        return response()->json([
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'sender_photo' => $message->sender->profile_photo_url,
            'created_at' => $message->created_at,
        ], 201);
    }

    public function readChannel(Request $request): JsonResponse
    {
        $channelId = $request->input('channel_id');
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->where('type', 'message')
            ->whereRaw("data->>'channel_id' = ?", [$channelId])
            ->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
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
