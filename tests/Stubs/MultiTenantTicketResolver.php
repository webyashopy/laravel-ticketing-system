<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;

/**
 * Testovací multi-tenant implementace {@see TicketTenantResolver}.
 *
 * Simuluje reálné per-tenant chování host aplikace (v reálné aplikaci je
 * tenant `Organization`). Slouží pro anti-IDOR a cross-tenant scénáře, které
 * reálná aplikace testuje přes `Organization` + parametr `?all_orgs=1`:
 *
 *   - `scopeQuery()` omezí query na `tickets.tenant_id` daného uživatele;
 *     pokud `$allTenants` je true A uživatel je superadmin, omezení vypadne.
 *   - `tenantIdFor()` vrátí `tenant_id` z testovacího User modelu.
 *   - `canViewAllTenants()` je pravda jen pro superadmina.
 *
 * Balíček sám tenant model nezná — tahle třída je čistě testovací stub
 * bindovaný v `defineEnvironment()` portovaných feature testů.
 */
final class MultiTenantTicketResolver implements TicketTenantResolver
{
    /**
     * Omezí query na tickety viditelné pro daného uživatele.
     *
     * Superadmin s `$allTenants = true` vidí napříč tenanty (žádné omezení).
     * Ostatní vždy jen svůj tenant. Běžný uživatel nemůže cross-tenant
     * pohled vynutit — `$allTenants` se u něj ignoruje (anti-IDOR).
     */
    public function scopeQuery(Builder $query, mixed $user, bool $allTenants = false): Builder
    {
        // Cross-tenant pohled jen pro superadmina, který si o něj explicitně
        // řekl. Běžný uživatel je vždy uzamčen na vlastní tenant.
        if ($allTenants && $this->canViewAllTenants($user)) {
            return $query;
        }

        return $query->where('tenant_id', $this->tenantIdFor($user));
    }

    /**
     * Tenant ID uživatele — čte se z atributu `tenant_id` testovacího Useru.
     */
    public function tenantIdFor(mixed $user): int|string|null
    {
        return $user->tenant_id ?? null;
    }

    /**
     * Cross-tenant viditelnost má jen superadmin.
     */
    public function canViewAllTenants(mixed $user): bool
    {
        return $user !== null && (bool) ($user->is_superadmin ?? false);
    }
}
