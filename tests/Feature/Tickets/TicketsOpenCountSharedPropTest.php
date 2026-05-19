<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Inertia\Testing\AssertableInertia as Assert;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Shared Inertia prop `ticketsOpenCount`.
 *
 * Balíček publikuje prop přes middleware {@see \Webyashopy\Tickets\Http\Middleware\ShareTicketsBadge}.
 * Počet otevřených ticketů jde přes `TicketTenantResolver` scope + `scopeOpen()`.
 *
 * ODCHYLKA od původní implementace: původní `HandleInertiaRequests::share()` počítal jen tickety,
 * jichž je aktuální user TVŮRCEM (per-user badge). Balíčkový `ShareTicketsBadge`
 * počítá otevřené tickety v tenant scope uživatele (per-tenant). Test je
 * proto přepsán na tenant-scope sémantiku: badge počítá open tickety
 * viditelné uživateli, ne striktně jím vytvořené.
 */
class TicketsOpenCountSharedPropTest extends BaseTicketTest
{
    public function test_shared_prop_obsahuje_pocet_open_ticketu_v_tenant_scope(): void
    {
        // 3 open tickety v tenantu usera
        Ticket::factory()->count(3)->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);
        // 1 closed (nesmí se počítat)
        Ticket::factory()->closed($this->user)->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);
        // 1 open ticket v jiném tenantu (nesmí se počítat — tenant scope)
        Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
            'status' => 'open',
        ]);

        $this->actingAs($this->user);

        // Použijeme libovolnou Inertia stránku — middleware sdílí prop globálně
        $response = $this->get('/tickets');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('ticketsOpenCount', 3)
        );
    }

    public function test_pro_neprihlaseneho_usera_share_neselze(): void
    {
        // Bez authu Inertia/auth middleware přesměruje na login,
        // share() middleware nesmí vyhodit exception.
        $response = $this->get('/tickets');

        $response->assertRedirect();
    }
}
