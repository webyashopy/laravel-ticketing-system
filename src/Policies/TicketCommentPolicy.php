<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Policies;

use Webyashopy\Tickets\Contracts\TicketAuthorizer;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketComment;

/**
 * Policy pro model TicketComment.
 *
 * Pravidla:
 *   - `create`: každý, kdo `view` ticket (deleguje na {@see TicketPolicy}).
 *   - `update`: jen autor, pouze do 5 minut po `created_at`
 *     (logika v `TicketComment::canBeEditedByUser()` — DRY).
 *   - `delete`: autor (vždy) nebo ten, kdo smí spravovat ticket
 *     (role-based přes {@see TicketAuthorizer}).
 *
 * KLÍČOVÉ: policy nevolá host-specifické role
 * metody (`isSuperAdmin()`) přímo — cross-tenant mazání komentářů řeší
 * bindovaný `TicketAuthorizer::canManage()` nad rodičovským ticketem.
 */
class TicketCommentPolicy
{
    /**
     * Smí uživatel vytvořit komentář na daném ticketu?
     *
     * Deleguje na `TicketPolicy::view` — kdo ticket vidí, ten může komentovat.
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function create(mixed $user, Ticket $ticket): bool
    {
        return app(TicketPolicy::class)->view($user, $ticket);
    }

    /**
     * Smí uživatel editovat komentář?
     *
     * Jen autor, pouze do 5 minut po vytvoření. Logika je v modelu
     * (`TicketComment::canBeEditedByUser()`) kvůli DRY (sdíleno s FE flagem).
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function update(mixed $user, TicketComment $comment): bool
    {
        if ($user === null) {
            return false;
        }

        return $comment->canBeEditedByUser($user);
    }

    /**
     * Smí uživatel smazat komentář?
     *
     * Autor (vždy) nebo uživatel se správou rodičovského ticketu —
     * role-based přes `TicketAuthorizer::canManage()` (cross-tenant
     * mazání řeší host implementace, default = jen tvůrce ticketu).
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function delete(mixed $user, TicketComment $comment): bool
    {
        if ($user === null) {
            return false;
        }

        // Autor svůj komentář smaže vždy.
        if ($comment->user_id === ($user->id ?? null)) {
            return true;
        }

        // Jinak cross-tenant mazání řeší autorizér nad rodičovským ticketem.
        $ticket = $comment->ticket;

        return $ticket !== null
            && app(TicketAuthorizer::class)->canManage($user, $ticket);
    }
}
