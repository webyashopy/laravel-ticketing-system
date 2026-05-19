<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Inertia\Testing\AssertableInertia as Assert;
use Webyashopy\Tickets\Models\Ticket;

/**
 * KRITICKÝ test: anti-IDOR scope na list ticketů.
 *
 * User A z tenantu 1 v GET /tickets NESMÍ vidět tickety z tenantu 2.
 * Izolaci řeší bindovaný MultiTenantTicketResolver::scopeQuery().
 */
class TicketAntiIdorListTest extends BaseTicketTest
{
    public function test_user_nevidi_tickety_jineho_tenanta_v_listu(): void
    {
        // Tickety v MÉM tenantu
        Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Můj ticket 1',
        ]);
        Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Můj ticket 2',
        ]);

        // Tickety v CIZÍM tenantu (nesmí být vidět)
        Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
            'title' => 'Cizí ticket 1',
        ]);
        Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
            'title' => 'Cizí ticket 2',
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/tickets');

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('tickets/index')
            ->has('tickets.data', 2)
            // Žádný řádek nesmí mít cizí ticket
            ->where('tickets.data.0.title', fn ($title) => str_starts_with((string) $title, 'Můj'))
            ->where('tickets.data.1.title', fn ($title) => str_starts_with((string) $title, 'Můj'))
        );

        // Detailnější ověření přes raw inertia data
        $page = $response->viewData('page');
        $titles = collect($page['props']['tickets']['data'])->pluck('title')->all();
        $this->assertNotContains('Cizí ticket 1', $titles);
        $this->assertNotContains('Cizí ticket 2', $titles);
    }
}
