<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Tests\Stubs\User;

/**
 * KRITICKÝ test: anti-IDOR na detail ticketu.
 *
 * User A z tenantu 1 v GET /tickets/{uuid} cizího ticketu tenantu 2 dostane 403.
 * Brání se přes TicketPolicy::view() (deleguje na TicketTenantResolver).
 */
class TicketAntiIdorShowTest extends BaseTicketTest
{
    public function test_user_dostane_403_pri_pokusu_o_detail_ciziho_tenanta(): void
    {
        // Cizí ticket v jiném tenantu
        $foreignTicket = Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/tickets/' . $foreignTicket->uuid);

        // Gate::authorize z policy → 403 (nebo 404, dle Laravel konfigurace)
        $this->assertContains($response->status(), [403, 404], 'Detail cizího ticketu musí být zakázán.');
    }

    public function test_user_smi_videt_detail_sveho_ticketu(): void
    {
        $myTicket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/tickets/' . $myTicket->uuid);

        $response->assertStatus(200);
    }

    public function test_user_smi_videt_ticket_kolegy_ve_stejnem_tenantu(): void
    {
        // Druhý user v MÉM tenantu
        $colleague = User::factory()->create([
            'tenant_id' => $this->tenantId,
        ]);

        $colleagueTicket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $colleague->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/tickets/' . $colleagueTicket->uuid);

        $response->assertStatus(200);
    }
}
