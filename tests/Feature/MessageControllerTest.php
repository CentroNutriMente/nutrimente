<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_and_load_a_direct_message(): void
    {
        $sender = $this->professionalUser();
        $recipient = $this->professionalUser();
        $channelId = $this->dmChannelId($sender->id, $recipient->id);

        $this->actingAs($sender)
            ->postJson('/messages', [
                'channel_type' => 'direct',
                'channel_id' => $channelId,
                'body' => 'Ciao, ci sei?',
            ])
            ->assertCreated()
            ->assertJsonPath('body', 'Ciao, ci sei?')
            ->assertJsonPath('sender_id', $sender->id);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'channel_type' => 'direct',
            'channel_id' => $channelId,
            'body' => 'Ciao, ci sei?',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipient->id,
            'type' => 'message',
        ]);

        $this->actingAs($recipient)
            ->getJson('/messages/load?channel_type=direct&channel_id='.$channelId)
            ->assertOk()
            ->assertJsonPath('0.body', 'Ciao, ci sei?');
    }

    public function test_marking_direct_channel_read_returns_unread_counts(): void
    {
        $sender = $this->professionalUser();
        $recipient = $this->professionalUser();
        $channelId = $this->dmChannelId($sender->id, $recipient->id);

        Message::create([
            'sender_id' => $sender->id,
            'channel_type' => 'direct',
            'channel_id' => $channelId,
            'body' => 'Promemoria',
        ]);

        Notification::send(
            $recipient->id,
            'message',
            'Messaggio diretto',
            'Promemoria',
            ['channel_type' => 'direct', 'channel_id' => $channelId],
        );
        $notification = Notification::first();

        $this->actingAs($recipient)
            ->postJson('/messages/read-channel', [
                'channel_type' => 'direct',
                'channel_id' => $channelId,
            ])
            ->assertOk()
            ->assertJsonPath('unread', []);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_direct_channel_id_can_be_sent_as_recipient_id(): void
    {
        $sender = $this->professionalUser();
        $recipient = $this->professionalUser();
        $channelId = $this->dmChannelId($sender->id, $recipient->id);

        $this->actingAs($sender)
            ->postJson('/messages', [
                'channel_type' => 'direct',
                'channel_id' => (string) $recipient->id,
                'body' => 'Canale normalizzato',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'channel_type' => 'direct',
            'channel_id' => $channelId,
            'body' => 'Canale normalizzato',
        ]);

        $this->assertSame($channelId, Notification::first()->data['channel_id']);
    }

    public function test_unread_counts_ignore_direct_messages_for_other_users(): void
    {
        $viewer = $this->professionalUser();
        $sender = $this->professionalUser();
        $recipient = $this->professionalUser();

        Message::create([
            'sender_id' => $sender->id,
            'channel_type' => 'direct',
            'channel_id' => $this->dmChannelId($sender->id, $recipient->id),
            'body' => 'Messaggio privato tra altri utenti',
        ]);

        $this->actingAs($viewer)
            ->getJson('/messages/unread')
            ->assertOk()
            ->assertJsonPath('unread', []);
    }

    public function test_user_cannot_load_direct_channel_they_are_not_part_of(): void
    {
        $viewer = $this->professionalUser();
        $sender = $this->professionalUser();
        $recipient = $this->professionalUser();

        $this->actingAs($viewer)
            ->getJson('/messages/load?channel_type=direct&channel_id='.$this->dmChannelId($sender->id, $recipient->id))
            ->assertForbidden();
    }

    public function test_poll_returns_direct_messages_when_new_messages_exist(): void
    {
        $sender = $this->professionalUser();
        $recipient = $this->professionalUser();
        $channelId = $this->dmChannelId($sender->id, $recipient->id);

        $message = Message::create([
            'sender_id' => $sender->id,
            'channel_type' => 'direct',
            'channel_id' => $channelId,
            'body' => 'Messaggio in tempo reale',
        ]);

        $response = $this->actingAs($recipient)
            ->getJson('/messages/poll?channel_type=direct&channel_id='.$channelId.'&last_id=0&last_unread_check=0')
            ->assertOk()
            ->assertJsonPath('messages.0.id', $message->id)
            ->assertJsonPath('messages.0.body', 'Messaggio in tempo reale');

        $data = $response->json();

        $this->assertSame(1, $data['unread_counts'][$channelId]);
        $this->assertSame(1, $data['unread_dm_counts'][$sender->id]);
    }

    public function test_team_message_notifications_are_sent_only_to_professional_profiles(): void
    {
        $sender = $this->professionalUser();
        $professional = $this->professionalUser();
        $roleOnlyUser = $this->roleOnlyUser();

        $this->actingAs($sender)
            ->postJson('/messages', [
                'channel_type' => 'team',
                'channel_id' => 'general',
                'body' => 'Aggiornamento team',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $professional->id,
            'type' => 'message',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $roleOnlyUser->id,
            'type' => 'message',
        ]);
    }

    public function test_recipient_can_discover_direct_message_from_sender_without_professional_profile(): void
    {
        $alfonso = $this->roleOnlyUser();
        $alfonso->update(['name' => 'Alfonso']);

        $sara = $this->professionalUser();
        $sara->update(['name' => 'Sara']);

        $channelId = $this->dmChannelId($alfonso->id, $sara->id);

        $this->actingAs($alfonso)
            ->postJson('/messages', [
                'channel_type' => 'direct',
                'channel_id' => $channelId,
                'body' => 'Ciao Sara, riesci a leggere questo messaggio?',
            ])
            ->assertCreated();

        $response = $this->actingAs($sara)->get('/messages');

        $response->assertOk();
        $this->assertTrue(
            collect($response->inertiaProps('colleagues'))->contains('id', $alfonso->id),
            'Sara should see Alfonso in the direct-message sidebar after Alfonso writes to her.'
        );
        $this->assertSame(1, $response->inertiaProps("unreadByChannel.{$channelId}"));
        $this->assertSame(1, $response->inertiaProps('notif_unread'));

        $this->actingAs($sara)
            ->getJson('/messages/load?channel_type=direct&channel_id='.$channelId)
            ->assertOk()
            ->assertJsonPath('0.body', 'Ciao Sara, riesci a leggere questo messaggio?');

        $this->actingAs($sara)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Messaggio diretto da Alfonso')
            ->assertJsonPath('notifications.0.data.channel_type', 'direct')
            ->assertJsonPath('notifications.0.data.channel_id', $channelId);
    }

    public function test_direct_message_sidebar_collapses_duplicate_display_names(): void
    {
        $viewer = $this->professionalUser();

        $duplicateSara = $this->professionalUser();
        $duplicateSara->update(['name' => 'Sara Alessandri']);

        $saraWithConversation = $this->professionalUser();
        $saraWithConversation->update(['name' => 'Sara Alessandri']);

        Message::create([
            'sender_id' => $saraWithConversation->id,
            'channel_type' => 'direct',
            'channel_id' => $this->dmChannelId($viewer->id, $saraWithConversation->id),
            'body' => 'Conversazione esistente',
        ]);

        $response = $this->actingAs($viewer)->get('/messages');
        $saras = collect($response->inertiaProps('colleagues'))
            ->where('name', 'Sara Alessandri')
            ->values();

        $response->assertOk();
        $this->assertCount(1, $saras);
        $this->assertSame($saraWithConversation->id, $saras->first()['id']);
    }

    private function professionalUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'psicologo']);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->professionalProfile()->create([
            'category' => 'psicologo',
            'slug' => 'professionista-'.$user->id,
        ]);

        return $user;
    }

    private function roleOnlyUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'psicologo']);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function dmChannelId(int $a, int $b): string
    {
        return 'dm_'.min($a, $b).'_'.max($a, $b);
    }
}
