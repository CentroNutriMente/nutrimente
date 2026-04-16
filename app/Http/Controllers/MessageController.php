<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function dmChannelId(int $a, int $b): string
    {
        return 'dm_' . min($a, $b) . '_' . max($a, $b);
    }

    /** Compute unread message counts per channel for $userId */
    private function unreadCounts(int $userId): array
    {
        // last_read_at per channel for this user
        $reads = DB::table('channel_reads')
            ->where('user_id', $userId)
            ->get()
            ->keyBy(fn ($r) => $r->channel_type . ':' . $r->channel_id);

        // Count messages by channel that arrived after last read
        $rows = DB::table('messages')
            ->whereNull('deleted_at')
            ->where('sender_id', '!=', $userId)
            ->select('channel_type', 'channel_id', DB::raw('MAX(created_at) as last_msg'), DB::raw('COUNT(*) as total'))
            ->groupBy('channel_type', 'channel_id')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $key     = $row->channel_type . ':' . $row->channel_id;
            $lastRead = $reads->get($key)?->last_read_at;
            if (!$lastRead || $row->last_msg > $lastRead) {
                $count = DB::table('messages')
                    ->whereNull('deleted_at')
                    ->where('sender_id', '!=', $userId)
                    ->where('channel_type', $row->channel_type)
                    ->where('channel_id', $row->channel_id)
                    ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
                    ->count();
                if ($count > 0) {
                    $counts[$row->channel_id] = $count;
                }
            }
        }

        return $counts;
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $user = $request->user();

        $teamChannels = [
            ['id' => 'general',       'label' => 'Generale'],
            ['id' => 'psicologo',     'label' => 'Psicologi'],
            ['id' => 'nutrizionista', 'label' => 'Nutrizionisti'],
            ['id' => 'osteopata',     'label' => 'Osteopati'],
        ];

        $colleagues = User::with('roles')
            ->whereHas('roles')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id'                => $u->id,
                'name'              => $u->name,
                'profile_photo_url' => $u->profile_photo_url,
            ]);

        return Inertia::render('Messages/Index', [
            'teamChannels'    => $teamChannels,
            'colleagues'      => $colleagues,
            'unreadByChannel' => $this->unreadCounts($user->id),
            'currentUser'     => [
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
            'channel_id'   => 'required|string',
        ]);

        $messages = Message::with('sender')
            ->where('channel_type', $request->channel_type)
            ->where('channel_id', $request->channel_id)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'body'        => $m->body,
                'sender_id'   => $m->sender_id,
                'sender_name' => $m->sender->name,
                'sender_photo'=> $m->sender->profile_photo_url,
                'created_at'  => $m->created_at,
            ]);

        return response()->json($messages);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_type' => 'required|in:team,direct',
            'channel_id'   => 'required|string',
            'body'         => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'sender_id'    => $request->user()->id,
            'channel_type' => $validated['channel_type'],
            'channel_id'   => $validated['channel_id'],
            'body'         => $validated['body'],
        ]);

        $message->load('sender');

        // Notifications
        $sender     = $request->user();
        $senderId   = $sender->id;
        $senderName = $sender->name;
        $preview    = mb_strimwidth($validated['body'], 0, 80, '…');

        if ($validated['channel_type'] === 'direct') {
            $parts       = explode('_', $validated['channel_id']); // dm_{a}_{b}
            $recipientId = collect([(int)($parts[1] ?? 0), (int)($parts[2] ?? 0)])
                ->first(fn ($id) => $id !== $senderId);
            if ($recipientId) {
                Notification::send(
                    $recipientId,
                    'message',
                    "Messaggio diretto da {$senderName}",
                    $preview,
                    ['channel_type' => 'direct', 'channel_id' => $validated['channel_id']]
                );
            }
        } else {
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
            'id'           => $message->id,
            'body'         => $message->body,
            'sender_id'    => $message->sender_id,
            'sender_name'  => $message->sender->name,
            'sender_photo' => $message->sender->profile_photo_url,
            'created_at'   => $message->created_at,
        ], 201);
    }

    /** Mark a channel as read (upsert last_read_at) and return updated unread counts */
    public function readChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_type' => 'required|in:team,direct',
            'channel_id'   => 'required|string',
        ]);

        $userId = $request->user()->id;

        DB::table('channel_reads')->upsert(
            [
                'user_id'      => $userId,
                'channel_type' => $validated['channel_type'],
                'channel_id'   => $validated['channel_id'],
                'last_read_at' => now(),
            ],
            ['user_id', 'channel_type', 'channel_id'],
            ['last_read_at']
        );

        // Also mark message notifications for this channel as read
        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->where('type', 'message')
            ->whereRaw("data->>'channel_id' = ?", [$validated['channel_id']])
            ->update(['read_at' => now()]);

        return response()->json(['unread' => $this->unreadCounts($userId)]);
    }

    /** Polled by the frontend every 8s to keep sidebar badges live */
    public function unread(Request $request): JsonResponse
    {
        return response()->json(['unread' => $this->unreadCounts($request->user()->id)]);
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        abort_if($message->sender_id !== $request->user()->id, 403);
        $message->update(['deleted_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
