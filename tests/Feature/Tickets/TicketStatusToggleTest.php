<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Webyashopy\Tickets\Models\Ticket;

/**
 * Close → Reopen lifecycle s auditem.
 *
 * Close: nastaví closed_at + closed_by_user_id, status=closed.
 * Reopen: vynuluje closed_at + closed_by_user_id, status=open + reset stale_email_sent_at.
 */
class TicketStatusToggleTest extends BaseTicketTest
{
    public function test_close_nastavi_closed_at_a_closed_by_user_id(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);

        $this->actingAs($this->user);

        $response = $this->post("/tickets/{$ticket->uuid}/close");
        $response->assertStatus(302);

        $ticket->refresh();
        $this->assertSame('closed', $ticket->status->value);
        $this->assertNotNull($ticket->closed_at);
        $this->assertSame($this->user->id, $ticket->closed_by_user_id);
    }

    public function test_reopen_vynuluje_closed_at_a_closed_by(): void
    {
        $ticket = Ticket::factory()->closed($this->user)->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'stale_email_sent_at' => now()->subDay(),
        ]);

        $this->assertNotNull($ticket->closed_at);
        $this->assertSame($this->user->id, $ticket->closed_by_user_id);

        $this->actingAs($this->user);

        $response = $this->post("/tickets/{$ticket->uuid}/reopen");
        $response->assertStatus(302);

        $ticket->refresh();
        $this->assertSame('open', $ticket->status->value);
        $this->assertNull($ticket->closed_at);
        $this->assertNull($ticket->closed_by_user_id);
        // Reopen taky resetuje stale flag.
        $this->assertNull($ticket->stale_email_sent_at);
    }

    public function test_zavreni_uz_uzavreneho_ticketu_je_idempotentni(): void
    {
        $ticket = Ticket::factory()->closed($this->user)->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $originalClosedAt = $ticket->closed_at;

        $this->actingAs($this->user);

        $response = $this->post("/tickets/{$ticket->uuid}/close");
        $response->assertStatus(302);

        $ticket->refresh();
        // closed_at se nezmění při idempotentním close
        $this->assertSame('closed', $ticket->status->value);
        $this->assertEquals(
            $originalClosedAt?->toDateTimeString(),
            $ticket->closed_at?->toDateTimeString(),
        );
    }

    public function test_cizi_user_nesmi_zavrit_ticket_jineho_tenanta(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->post("/tickets/{$ticket->uuid}/close");

        $this->assertContains($response->status(), [403, 404]);

        $ticket->refresh();
        $this->assertSame('open', $ticket->status->value);
    }
}
