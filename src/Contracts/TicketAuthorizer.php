<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Contracts;

/**
 * Kontrakt pro role-based autorizaci ticketů.
 *
 * Řeší POUZE role — kdo smí ticket editovat/zavírat a kdo ho smí smazat.
 * Izolaci dat (per-tenant viditelnost) řeší samostatný kontrakt
 * {@see TicketTenantResolver}.
 *
 * Balíček dodává default {@see \Webyashopy\Tickets\Support\OwnerTicketAuthorizer}
 * (správa i mazání pouze tvůrcem ticketu). Host aplikace si v service
 * provideru nabinduje vlastní implementaci (např. org admin / superadmin).
 *
 * Parametr `$ticket` je typovaný volně (`mixed`) — balíčkový model Ticket
 * vznikne v host aplikaci a kontrakt na něj nesmí mít tvrdou vazbu.
 */
interface TicketAuthorizer
{
    /**
     * Smí uživatel ticket spravovat (editovat / zavřít / znovuotevřít)?
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     * @param  mixed  $ticket  Instance modelu Ticket.
     */
    public function canManage(mixed $user, mixed $ticket): bool;

    /**
     * Smí uživatel ticket smazat (hard delete)?
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     * @param  mixed  $ticket  Instance modelu Ticket.
     */
    public function canDelete(mixed $user, mixed $ticket): bool;
}
