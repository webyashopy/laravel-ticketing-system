<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Kontrakt pro multi-tenancy izolaci ticketů.
 *
 * Řeší POUZE izolaci dat — kdo které tickety vidí. Role-based autorizaci
 * (kdo smí zavírat/mazat) řeší samostatný kontrakt {@see TicketAuthorizer}.
 *
 * Balíček dodává default {@see \Webyashopy\Tickets\Support\NullTenantResolver}
 * (single-tenant pass-through). Multi-tenant host aplikace si v service
 * provideru nabinduje vlastní implementaci (např. per-organization scope).
 *
 * Closures v configu nejsou možné (`config:cache` je neumí serializovat),
 * proto je tenancy bindovaný kontrakt, ne config closure.
 */
interface TicketTenantResolver
{
    /**
     * Omezí query na tickety viditelné pro daného uživatele.
     *
     * Single-tenant implementace vrací query beze změny. Multi-tenant
     * implementace přidá `where('tenant_id', ...)`. Pokud `$allTenants`
     * je true a uživatel na to má právo ({@see canViewAllTenants()}),
     * smí implementace vrátit query bez tenant omezení.
     *
     * @param  Builder  $query  Eloquent query nad modelem Ticket.
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     * @param  bool  $allTenants  Cross-tenant pohled (např. `?all_orgs=1`).
     */
    public function scopeQuery(Builder $query, mixed $user, bool $allTenants = false): Builder;

    /**
     * Vrátí identifikátor tenanta pro daného uživatele.
     *
     * Hodnota se zapisuje do sloupce `tickets.tenant_id` při vytvoření
     * ticketu. Single-tenant implementace vrací `null`.
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function tenantIdFor(mixed $user): int|string|null;

    /**
     * Smí uživatel vidět tickety napříč všemi tenanty?
     *
     * Typicky pravda jen pro superadmina. Single-tenant implementace
     * vrací vždy `false`.
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     */
    public function canViewAllTenants(mixed $user): bool;
}
