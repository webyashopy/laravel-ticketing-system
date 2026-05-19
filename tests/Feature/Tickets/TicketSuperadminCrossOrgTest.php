<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Inertia\Testing\AssertableInertia as Assert;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Cross-tenant viditelnost superadmina.
 *
 * Přepsáno z původní implementace `TicketSuperadminCrossOrgTest` — test
 * resolveru místo `Organization` modelu. Cross-tenant logiku řeší bindovaný
 * {@see \Webyashopy\Tickets\Tests\Stubs\MultiTenantTicketResolver}.
 *
 * ODCHYLKA od původní implementace — chování balíčku:
 *   Původní `TicketController` ukazoval superadminovi cross-org pohled jen na
 *   explicitní `?all_orgs=1`. Balíčkový controller má jiné
 *   pravidlo: `$allTenants = canViewAllTenants($user) || ?all_orgs`. Kdo
 *   tedy smí cross-tenant, vidí napříč tenanty i BEZ parametru. Test proto
 *   ověřuje: superadmin vidí všechny tenanty vždy, běžný user nikdy
 *   (anti-IDOR — `?all_orgs=1` u běžného usera scope ignoruje).
 */
class TicketSuperadminCrossOrgTest extends BaseTicketTest
{
    public function test_superadmin_vidi_tickety_napric_tenanty(): void
    {
        // Tickety v různých tenantech
        Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Tenant A ticket',
        ]);
        Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
            'title' => 'Tenant B ticket',
        ]);

        $superAdmin = $this->superAdmin($this->tenantId);

        $this->actingAs($superAdmin);

        // S all_orgs=1 → vidí oba (canViewAllTenants() je true)
        $response = $this->get('/tickets?all_orgs=1');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('tickets/index')
            ->has('tickets.data', 2)
            ->where('filters.all_orgs', true)
        );
    }

    public function test_superadmin_vidi_napric_tenanty_i_bez_parametru(): void
    {
        // Pozn.: oproti původní implementaci balíček nechá superadmina vidět cross-tenant
        // i bez ?all_orgs — řídí `canViewAllTenants()` v controlleru.
        Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
        ]);

        $superAdmin = $this->superAdmin($this->tenantId);

        $this->actingAs($superAdmin);

        $response = $this->get('/tickets');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('tickets.data', 2)
            ->where('filters.all_orgs', true)
        );
    }

    public function test_bezny_user_s_all_orgs_neudela_cross_tenant(): void
    {
        // Anti-IDOR: běžný user může poslat ?all_orgs=1, ale scope to ignoruje
        Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/tickets?all_orgs=1');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('tickets.data', 1)
            // can.viewAllOrgs musí být false pro běžného usera
            ->where('can.viewAllOrgs', false)
        );
    }
}
