<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Support;

use Webyashopy\Tickets\Contracts\TicketAuthorizer;

/**
 * Výchozí default pro {@see TicketAuthorizer}.
 *
 * Nejjednodušší možná autorizace: ticket smí spravovat i mazat pouze
 * jeho tvůrce (porovnání `$ticket->user_id === $user->id`).
 *
 * Host aplikace s rolemi (org admin, superadmin) tento default přepíše
 * vlastním bindingem v service provideru.
 */
class OwnerTicketAuthorizer implements TicketAuthorizer
{
    /**
     * Spravovat ticket smí jen jeho tvůrce.
     */
    public function canManage(mixed $user, mixed $ticket): bool
    {
        return $this->isOwner($user, $ticket);
    }

    /**
     * Smazat ticket smí jen jeho tvůrce.
     */
    public function canDelete(mixed $user, mixed $ticket): bool
    {
        return $this->isOwner($user, $ticket);
    }

    /**
     * Je uživatel tvůrcem ticketu?
     *
     * Porovnání je striktní (`===`) — `user_id` ticketu i `id` uživatele
     * jsou typově stejné (oba int z DB). Pokud kterýkoli chybí, vrací false.
     */
    private function isOwner(mixed $user, mixed $ticket): bool
    {
        if ($user === null || $ticket === null) {
            return false;
        }

        return $ticket->user_id === $user->id;
    }
}
