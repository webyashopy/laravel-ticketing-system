<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Support;

use Illuminate\Database\Eloquent\Builder;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;

/**
 * Single-tenant default pro {@see TicketTenantResolver}.
 *
 * Pass-through implementace pro projekty bez multi-tenancy:
 *   - scopeQuery vrací query beze změny (žádné `where('tenant_id', ...)`),
 *   - tenantIdFor vrací `null` (sloupec `tickets.tenant_id` zůstává NULL),
 *   - canViewAllTenants vrací vždy `false`.
 *
 * Multi-tenant host aplikace tento default přepíše vlastním bindingem
 * v service provideru.
 */
class NullTenantResolver implements TicketTenantResolver
{
    /**
     * Single-tenant: žádné omezení, query se vrací beze změny.
     */
    public function scopeQuery(Builder $query, mixed $user, bool $allTenants = false): Builder
    {
        return $query;
    }

    /**
     * Single-tenant: žádný tenant identifikátor.
     */
    public function tenantIdFor(mixed $user): int|string|null
    {
        return null;
    }

    /**
     * Single-tenant: cross-tenant pohled nedává smysl.
     */
    public function canViewAllTenants(mixed $user): bool
    {
        return false;
    }
}
