<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Notifications\TicketCommentCreatedNotification;
use Webyashopy\Tickets\Notifications\TicketStatusChangedNotification;
use Webyashopy\Tickets\Notifications\TicketUpdatedNotification;

/**
 * Dispatcher ticketových notifikací.
 *
 * Příjemci:
 *  - Všichni účastníci konverzace (User instance host aplikace, full notify
 *    mail + db): set = { autor ticketu } ∪ { distinct autoři komentářů }.
 *  - Centrální adresa (`config('tickets.stale_email_to')`) — pouze mail.
 *
 * POZN.: balíček User model nezná napřímo — čte ho z configu
 * `tickets.models.user_model`. Žádná přímá vazba na notifikační
 * systém host aplikace; notifikace jdou standardním Laravel `Notifiable` kanálem
 * (mail + database). Recipienty řeší výhradně relace ticketu + config,
 * NE host-specifický resolver.
 *
 * Self-notification:
 *  Pokud je `$actor` autorem ticketu / komentujícím (případně shoda emailu se
 *  centrální adresou), daný kanál se přeskočí — žádné echo svým akcím.
 *
 * Debounce per typ:
 *  Atomický check-and-set přes `Cache::add()` — pokud klíč existuje, akce
 *  se přeskočí. Per-recipient + per-type klíč zabrání flood, ale stále
 *  povolí různé typy notifikací paralelně (např. comment + status změna).
 *
 *  Délka okna podle typu:
 *  - `comment` = 5 s — chat-like konverzace, jen anti-double-submit /
 *    burst coalesce; 2. komentář během minuty MUSÍ vyrobit notifikaci.
 *  - `status` + `updated` = 60 s — burst-protection při sérii rychlých akcí.
 */
final class TicketNotificationDispatcher
{
    private const TYPE_COMMENT = 'comment';

    private const TYPE_STATUS = 'status';

    private const TYPE_UPDATED = 'updated';

    /**
     * Default délka debounce okna (BC defenziva pro `acquireDebounce()`).
     */
    private const DEBOUNCE_SECONDS = 60;

    /**
     * Mapa typ notifikace → délka debounce okna v sekundách.
     *
     * @var array<string, int>
     */
    private const DEBOUNCE_BY_TYPE = [
        self::TYPE_COMMENT => 5,
        self::TYPE_STATUS => 60,
        self::TYPE_UPDATED => 60,
    ];

    /**
     * Pošle notifikaci o novém komentáři.
     *
     * @param  object  $comment  TicketComment (uuid, body) — duck-typed
     * @param  object  $actor  User instance host aplikace (duck-typed: id, email)
     */
    public function dispatchCommentCreated(Ticket $ticket, object $comment, object $actor): void
    {
        $notification = fn (): TicketCommentCreatedNotification => new TicketCommentCreatedNotification(
            ticket: $ticket,
            comment: $comment,
            actor: $actor,
        );

        $this->dispatchToRecipients($ticket, $actor, self::TYPE_COMMENT, $notification);
    }

    /**
     * Pošle notifikaci o změně stavu (close / reopen).
     *
     * @param  string  $action  'closed' | 'reopened'
     * @param  object  $actor  User instance host aplikace (duck-typed: id, email)
     */
    public function dispatchStatusChanged(Ticket $ticket, string $action, object $actor): void
    {
        $notification = fn (): TicketStatusChangedNotification => new TicketStatusChangedNotification(
            ticket: $ticket,
            action: $action,
            actor: $actor,
        );

        $this->dispatchToRecipients($ticket, $actor, self::TYPE_STATUS, $notification);
    }

    /**
     * Pošle notifikaci o editaci ticketu (změna polí).
     *
     * @param  array<int, array{field: string, old: mixed, new: mixed}>  $changedFields
     * @param  object  $actor  User instance host aplikace (duck-typed: id, email)
     */
    public function dispatchUpdated(Ticket $ticket, array $changedFields, object $actor): void
    {
        // Bez změn nic neposíláme (defenzivní guard).
        if ($changedFields === []) {
            return;
        }

        $notification = fn (): TicketUpdatedNotification => new TicketUpdatedNotification(
            ticket: $ticket,
            changedFields: $changedFields,
            actor: $actor,
        );

        $this->dispatchToRecipients($ticket, $actor, self::TYPE_UPDATED, $notification);
    }

    /**
     * Centrální distribuce — projde recipients a aplikuje debounce.
     *
     * @param  callable(): \Illuminate\Notifications\Notification  $factory
     *         Factory pro Notification instanci.
     */
    private function dispatchToRecipients(
        Ticket $ticket,
        object $actor,
        string $type,
        callable $factory,
    ): void {
        // 1) Účastníci konverzace — User instance, oba kanály (mail + database).
        $debounceSeconds = $this->debounceSecondsFor($type);

        foreach ($this->resolveParticipants($ticket, $actor) as $participant) {
            if ($this->acquireDebounce($ticket, $participant->id, $type, $debounceSeconds)) {
                $participant->notify($factory());
            }
        }

        // 2) Centrální adresa — anon mail route, pouze mail kanál.
        $centralEmail = (string) config('tickets.stale_email_to', '');

        if ($centralEmail !== '' && ! $this->isSameAsActor($centralEmail, $actor)) {
            // Kompozitní recipient ID pro debounce klíč (email místo numeric id)
            $recipientKey = 'email:' . md5($centralEmail);

            if ($this->acquireDebounce($ticket, $recipientKey, $type, $debounceSeconds)) {
                Notification::route('mail', $centralEmail)->notify($factory());
            }
        }
    }

    /**
     * Sestaví okruh příjemců = všichni účastníci konverzace.
     *
     * Set = { autor ticketu } ∪ { distinct autoři komentářů ticketu },
     * dedup podle `user_id`, vyřazen `$actor` (self-notification skip).
     * Smazaný autor komentáře (`user_id = NULL`) je odfiltrován přes
     * `whereNotNull`. Tenant filtr se nepoužívá — cross-tenant účastník
     * (superadmin komentující cizí ticket) má o dění vědět.
     *
     * @return array<int, object> Příjemci (User instance) indexovaní podle `user_id`
     */
    private function resolveParticipants(Ticket $ticket, object $actor): array
    {
        // Autoři komentářů — jen distinct existující user_id (query, ne kolekce).
        // reorder() zruší dědičné orderBy('created_at') z relace comments();
        // Postgres odmítá ORDER BY na sloupci mimo SELECT DISTINCT (42P10).
        $commenterIds = $ticket->comments()
            ->whereNotNull('user_id')
            ->reorder()
            ->distinct()
            ->pluck('user_id')
            ->all();

        /** @var array<int, object> $participants */
        $participants = [];

        // Autor ticketu (creator) — pokud existuje a není to actor.
        $creator = $ticket->creator;

        if ($creator !== null && $creator->id !== $actor->id) {
            $participants[$creator->id] = $creator;
        }

        // Doplníme komentující, kteří ještě v setu nejsou — vyjma actora.
        $missingIds = array_filter(
            $commenterIds,
            fn (int $id): bool => $id !== $actor->id && ! isset($participants[$id]),
        );

        if ($missingIds !== []) {
            // User model čteme z configu — balíček ho nezná napřímo.
            $userModel = $this->userModel();

            foreach ($userModel::query()->whereIn('id', $missingIds)->get() as $commenter) {
                $participants[$commenter->id] = $commenter;
            }
        }

        return $participants;
    }

    /**
     * Délka debounce okna pro daný typ notifikace.
     */
    private function debounceSecondsFor(string $type): int
    {
        return self::DEBOUNCE_BY_TYPE[$type] ?? self::DEBOUNCE_SECONDS;
    }

    /**
     * Atomický check-and-set na debounce key.
     * Vrátí true pokud klíč byl právě vytvořen (notif pošleme),
     * false pokud existoval (skip).
     */
    private function acquireDebounce(
        Ticket $ticket,
        int|string $recipientKey,
        string $type,
        int $seconds = self::DEBOUNCE_SECONDS,
    ): bool {
        $cacheKey = "ticket-notif:{$ticket->uuid}:{$recipientKey}:{$type}";

        return Cache::add($cacheKey, 1, $seconds);
    }

    /**
     * Pokud centrální email odpovídá actor.email, považujeme to za self-akci.
     */
    private function isSameAsActor(string $email, object $actor): bool
    {
        return strcasecmp(trim($email), trim((string) ($actor->email ?? ''))) === 0;
    }

    /**
     * Třída User modelu host aplikace (z configu).
     *
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        /** @var class-string<Model> $model */
        $model = config('tickets.models.user_model', 'App\\Models\\User');

        return $model;
    }
}
