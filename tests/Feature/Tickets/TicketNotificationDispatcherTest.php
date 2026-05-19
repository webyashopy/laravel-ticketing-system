<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketComment;
use Webyashopy\Tickets\Notifications\TicketCommentCreatedNotification;
use Webyashopy\Tickets\Notifications\TicketStatusChangedNotification;
use Webyashopy\Tickets\Notifications\TicketUpdatedNotification;
use Webyashopy\Tickets\Services\TicketNotificationDispatcher;
use Webyashopy\Tickets\Tests\Stubs\User;

/**
 * Testy pro TicketNotificationDispatcher.
 *
 * Pokrývá:
 *  - dispatch comment / status / updated → email + DB notif autorovi
 *  - exclude actor (žádné self-notification)
 *  - debounce per typ (comment 5 s, status/updated 60 s)
 *  - email subject format `[Tickets] Ticket #{uuid_short}: ...`
 *  - action_url v database notifikaci
 *  - okruh příjemců = všichni účastníci konverzace
 */
class TicketNotificationDispatcherTest extends BaseTicketTest
{
    private TicketNotificationDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new TicketNotificationDispatcher();

        // Fixní centrální email pro deterministické testy.
        config(['tickets.stale_email_to' => 'admin@example.com']);

        // Cache flush — odstraní debounce klíče mezi testy.
        Cache::flush();
    }

    /**
     * Vytvoří stub TicketComment objekt (duck-typed: uuid + body).
     */
    private function makeComment(string $body = 'Test komentář'): object
    {
        return (object) [
            'uuid' => (string) Str::uuid(),
            'body' => $body,
        ];
    }

    public function test_comment_created_posle_email_autorovi_ticketu(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $commenter = User::factory()->create(['tenant_id' => $this->tenantId]);
        $comment = $this->makeComment();

        $this->dispatcher->dispatchCommentCreated($ticket, $comment, $commenter);

        Notification::assertSentTo(
            $this->user,
            TicketCommentCreatedNotification::class,
            function (TicketCommentCreatedNotification $notif) use ($ticket): bool {
                return $notif->ticket->is($ticket);
            },
        );
    }

    public function test_comment_created_neposle_pokud_je_autor_komentare_stejny_jako_autor_ticketu(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Actor === ticket creator → self-notification skip
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment(), $this->user);

        Notification::assertNotSentTo($this->user, TicketCommentCreatedNotification::class);
    }

    public function test_60s_debounce_blokuje_druhy_email(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        // 2× rychle za sebou (comment debounce je 5 s)
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment('1.'), $actor);
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment('2.'), $actor);

        // Autor ticketu dostane jen 1× (druhý zablokován debounce)
        Notification::assertSentToTimes($this->user, TicketCommentCreatedNotification::class, 1);
    }

    public function test_debounce_pousti_email_po_okne(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment('1.'), $actor);

        // Travel + 61s — comment debounce (5 s) dávno vypršel
        $this->travel(61)->seconds();

        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment('2.'), $actor);

        Notification::assertSentToTimes($this->user, TicketCommentCreatedNotification::class, 2);
    }

    public function test_status_changed_close(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        $this->dispatcher->dispatchStatusChanged($ticket, 'closed', $actor);

        Notification::assertSentTo(
            $this->user,
            TicketStatusChangedNotification::class,
            function (TicketStatusChangedNotification $notif): bool {
                return $notif->action === 'closed';
            },
        );
    }

    public function test_status_changed_reopen(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        $this->dispatcher->dispatchStatusChanged($ticket, 'reopened', $actor);

        Notification::assertSentTo(
            $this->user,
            TicketStatusChangedNotification::class,
            function (TicketStatusChangedNotification $notif): bool {
                return $notif->action === 'reopened';
            },
        );
    }

    public function test_updated_obsahuje_seznam_changed_fields(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        $changes = [
            ['field' => 'priority', 'old' => 'low', 'new' => 'high'],
            ['field' => 'category', 'old' => 'bug', 'new' => 'feature'],
        ];

        $this->dispatcher->dispatchUpdated($ticket, $changes, $actor);

        Notification::assertSentTo(
            $this->user,
            TicketUpdatedNotification::class,
            function (TicketUpdatedNotification $notif) use ($changes): bool {
                return $notif->changedFields === $changes;
            },
        );
    }

    public function test_database_notification_obsahuje_action_url(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);
        $comment = $this->makeComment();

        $this->dispatcher->dispatchCommentCreated($ticket, $comment, $actor);

        Notification::assertSentTo(
            $this->user,
            TicketCommentCreatedNotification::class,
            function (TicketCommentCreatedNotification $notif) use ($ticket, $comment): bool {
                $payload = $notif->toDatabase($this->user);

                return $payload['action_url'] === "/tickets/{$ticket->uuid}#comment-{$comment->uuid}";
            },
        );
    }

    public function test_email_subject_format(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'uuid' => 'abcd1234-aaaa-bbbb-cccc-ddddeeeeffff',
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        // Sestavíme notifikaci přímo a ověříme subject (app.name = Tickets z base TestCase)
        $notif = new TicketCommentCreatedNotification($ticket, $this->makeComment(), $actor);
        $mail = $notif->toMail($this->user);

        $this->assertSame('[Tickets] Ticket #abcd1234: nový komentář', $mail->subject);

        // Status changed (closed)
        $notifStatus = new TicketStatusChangedNotification($ticket, 'closed', $actor);
        $mailStatus = $notifStatus->toMail($this->user);
        $this->assertSame('[Tickets] Ticket #abcd1234: uzavřen', $mailStatus->subject);

        // Updated
        $notifUpd = new TicketUpdatedNotification($ticket, [['field' => 'priority', 'old' => 'low', 'new' => 'high']], $actor);
        $mailUpd = $notifUpd->toMail($this->user);
        $this->assertSame('[Tickets] Ticket #abcd1234: upraveno', $mailUpd->subject);
    }

    public function test_centralni_adresa_dostane_email_anon_routou(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment(), $actor);

        // Anonymous notifiable na centrální email z configu
        Notification::assertSentTo(
            new AnonymousNotifiable(),
            TicketCommentCreatedNotification::class,
            function ($notif, $channels, AnonymousNotifiable $notifiable): bool {
                return $notifiable->routes['mail'] === 'admin@example.com';
            },
        );
    }

    // --- okruh příjemců = všichni účastníci konverzace ---

    public function test_komentujici_ne_tvurce_dostane_notifikaci(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Komentující, který NENÍ tvůrcem ticketu.
        $commenter = User::factory()->create(['tenant_id' => $this->tenantId]);
        TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $commenter->id,
        ]);

        // Akci provede tvůrce ticketu → komentující musí dostat notifikaci.
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment(), $this->user);

        Notification::assertSentTo($commenter, TicketCommentCreatedNotification::class);
    }

    public function test_vice_komentujicich_dostane_notifikaci(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $commenterA = User::factory()->create(['tenant_id' => $this->tenantId]);
        $commenterB = User::factory()->create(['tenant_id' => $this->tenantId]);

        TicketComment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $commenterA->id]);
        TicketComment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $commenterB->id]);

        // Actor je třetí strana → tvůrce i oba komentující dostanou notifikaci.
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);
        $this->dispatcher->dispatchStatusChanged($ticket, 'closed', $actor);

        Notification::assertSentTo($this->user, TicketStatusChangedNotification::class);
        Notification::assertSentTo($commenterA, TicketStatusChangedNotification::class);
        Notification::assertSentTo($commenterB, TicketStatusChangedNotification::class);
    }

    public function test_actor_je_vzdy_vyrazen_i_kdyz_je_komentujici(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Komentující, který zároveň provádí aktuální akci.
        $commenter = User::factory()->create(['tenant_id' => $this->tenantId]);
        TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $commenter->id,
        ]);

        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment(), $commenter);

        // Actor (komentující) nedostane nic, tvůrce ticketu ano.
        Notification::assertNotSentTo($commenter, TicketCommentCreatedNotification::class);
        Notification::assertSentTo($this->user, TicketCommentCreatedNotification::class);
    }

    public function test_dedup_tvurce_ktery_i_komentoval_dostane_jednu_notifikaci(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Tvůrce ticketu zároveň 2× komentoval → stále jen 1 příjemce.
        TicketComment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $this->user->id]);
        TicketComment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $this->user->id]);

        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment(), $actor);

        Notification::assertSentToTimes($this->user, TicketCommentCreatedNotification::class, 1);
    }

    public function test_smazany_autor_komentare_neshodi_dispatch(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Komentář se smazaným autorem (user_id = NULL) — whereNotNull ho odfiltruje.
        TicketComment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => null]);

        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment(), $actor);

        // Tvůrce ticketu pořád dostane notifikaci, dispatch neselže.
        Notification::assertSentTo($this->user, TicketCommentCreatedNotification::class);
    }

    public function test_comment_debounce_je_5s_bloknuti(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        // 2 komentáře během okna 5 s → debounce zablokuje druhý.
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment('1.'), $actor);
        $this->travel(3)->seconds();
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment('2.'), $actor);

        Notification::assertSentToTimes($this->user, TicketCommentCreatedNotification::class, 1);
    }

    public function test_comment_debounce_je_5s_pousti_po_5s(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        // Druhý komentář po 6 s → debounce okno comment (5 s) už vypršelo.
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment('1.'), $actor);
        $this->travel(6)->seconds();
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment('2.'), $actor);

        Notification::assertSentToTimes($this->user, TicketCommentCreatedNotification::class, 2);
    }

    public function test_status_debounce_zustava_60s(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);

        // Po 6 s je status debounce (60 s) stále aktivní → druhá notif zablokována.
        $this->dispatcher->dispatchStatusChanged($ticket, 'closed', $actor);
        $this->travel(6)->seconds();
        $this->dispatcher->dispatchStatusChanged($ticket, 'reopened', $actor);

        Notification::assertSentToTimes($this->user, TicketStatusChangedNotification::class, 1);
    }

    public function test_cross_tenant_komentujici_dostane_notifikaci(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Komentující z JINÉHO tenanta (superadmin komentující cizí ticket) —
        // resolveParticipants() nemá tenant filtr, takže je účastník a musí
        // dostat notifikaci.
        $crossTenantUser = User::factory()->create([
            'tenant_id' => $this->otherTenantId,
        ]);
        TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $crossTenantUser->id,
        ]);

        $actor = User::factory()->create(['tenant_id' => $this->tenantId]);
        $this->dispatcher->dispatchCommentCreated($ticket, $this->makeComment(), $actor);

        Notification::assertSentTo($crossTenantUser, TicketCommentCreatedNotification::class);
    }
}
