<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Policies;

use Webyashopy\Tickets\Contracts\TicketAuthorizer;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Policy pro model Ticket.
 *
 * Pravidla:
 *   - `viewAny`: každý autentizovaný uživatel (seznam je dál scope-ován
 *     per-tenant přes {@see TicketTenantResolver}).
 *   - `view`: per-tenant viditelnost — deleguje na `TicketTenantResolver`.
 *   - `create`: každý autentizovaný uživatel.
 *   - `update` (close/reopen/edit) a `delete`: role-based — delegují na
 *     bindovaný kontrakt {@see TicketAuthorizer}.
 *
 * KLÍČOVÉ: policy NESMÍ volat host-specifické role
 * metody (`isSuperAdmin()` / `isOrganizationAdmin()`) přímo. Role-based
 * autorizace jde výhradně přes `TicketAuthorizer`, per-tenant viditelnost
 * přes `TicketTenantResolver`. Host aplikace si oba kontrakty nabinduje
 * vlastní implementací.
 */
class TicketPolicy
{
    /**
     * Uživatel může vidět seznam ticketů.
     *
     * Vlastní per-tenant filtrace probíhá v HTTP vrstvě přes
     * `TicketTenantResolver::scopeQuery()` — policy jen pustí dál
     * každého autentizovaného uživatele.
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function viewAny(mixed $user): bool
    {
        return $user !== null;
    }

    /**
     * Anti-IDOR check: smí uživatel vidět konkrétní ticket?
     *
     * Per-tenant viditelnost — ověřujeme přes `TicketTenantResolver`:
     * pokud ticket projde scope query nad svým vlastním ID, je viditelný.
     * Single-tenant `NullTenantResolver` query nemění → každý vidí vše.
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function view(mixed $user, Ticket $ticket): bool
    {
        if ($user === null) {
            return false;
        }

        $resolver = app(TicketTenantResolver::class);

        // Cross-tenant pohled — superadmin apod. vidí vše.
        if ($resolver->canViewAllTenants($user)) {
            return true;
        }

        // Ticket je viditelný, pokud projde tenant scope nad vlastním ID.
        return $resolver
            ->scopeQuery(Ticket::query()->whereKey($ticket->getKey()), $user)
            ->exists();
    }

    /**
     * Každý autentizovaný uživatel může nahlásit nový ticket.
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function create(mixed $user): bool
    {
        return $user !== null;
    }

    /**
     * Update (edit title/description/category/priority, close, reopen).
     *
     * Role-based — deleguje na bindovaný `TicketAuthorizer::canManage()`.
     * Default `OwnerTicketAuthorizer` = jen tvůrce; host aplikace si
     * nabinduje vlastní (org admin / superadmin apod.).
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function update(mixed $user, Ticket $ticket): bool
    {
        if ($user === null) {
            return false;
        }

        return app(TicketAuthorizer::class)->canManage($user, $ticket);
    }

    /**
     * Hard delete ticketu.
     *
     * Role-based — deleguje na bindovaný `TicketAuthorizer::canDelete()`.
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function delete(mixed $user, Ticket $ticket): bool
    {
        if ($user === null) {
            return false;
        }

        return app(TicketAuthorizer::class)->canDelete($user, $ticket);
    }
}
