<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Support\Str;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAuditLog;
use Webyashopy\Tickets\Tests\Stubs\User;

/**
 * Testy pro PATCH /tickets/{uuid} edit endpoint.
 *
 * Kryje:
 *   - Autorizaci (creator, superadmin, ostatní)
 *   - Audit log integraci (recordMany per pole, no-op skip)
 *   - Validační pravidla (max length, enum)
 *   - Partial update (pouze poslané pole)
 *   - Anti-IDOR cross-tenant (404/403)
 */
class TicketUpdateTest extends BaseTicketTest
{
    public function test_creator_muze_aktualizovat_vlastni_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Původní název',
            'description' => 'Původní popis',
            'category' => 'bug',
            'priority' => 'low',
        ]);

        $this->actingAs($this->user);

        $response = $this->patch("/tickets/{$ticket->uuid}", [
            'title' => 'Nový název',
            'description' => 'Nový popis',
            'category' => 'feature',
            'priority' => 'high',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame('Nový název', $ticket->title);
        $this->assertSame('Nový popis', $ticket->description);
        $this->assertSame('feature', $ticket->category->value);
        $this->assertSame('high', $ticket->priority->value);
    }

    public function test_superadmin_muze_aktualizovat_libovolny_ticket(): void
    {
        // Ticket cizího tenanta — superadmin přesto smí editovat
        // (MultiTenantTicketAuthorizer::canManage → true pro superadmina).
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
            'title' => 'Cizí ticket',
        ]);

        $superAdmin = $this->superAdmin();
        $this->actingAs($superAdmin);

        $response = $this->patch("/tickets/{$ticket->uuid}", [
            'title' => 'Upraveno superadminem',
        ]);

        $response->assertStatus(302);
        $ticket->refresh();
        $this->assertSame('Upraveno superadminem', $ticket->title);
    }

    public function test_other_user_nemuze_aktualizovat_403(): void
    {
        // Jiný user ve stejném tenantu, ale není tvůrce ani superadmin.
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Cizí název',
        ]);

        $intruder = User::factory()->create([
            'tenant_id' => $this->tenantId,
        ]);

        $this->actingAs($intruder);

        $response = $this->patch("/tickets/{$ticket->uuid}", [
            'title' => 'Hack pokus',
        ]);

        $this->assertContains($response->status(), [403, 404]);

        $ticket->refresh();
        $this->assertSame('Cizí název', $ticket->title);
    }

    public function test_audit_log_zaznamena_kazdou_zmenenou_polozku(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'A',
            'description' => 'B',
            'category' => 'bug',
            'priority' => 'low',
        ]);

        $this->actingAs($this->user);

        $this->patch("/tickets/{$ticket->uuid}", [
            'title' => 'A2',
            'description' => 'B2',
            'category' => 'feature',
            'priority' => 'high',
        ])->assertStatus(302);

        // 4 audit log eventy (jeden per pole)
        $logs = TicketAuditLog::where('ticket_id', $ticket->id)->get();
        $this->assertCount(4, $logs);

        $byField = $logs->keyBy('field');
        $this->assertSame('A', $byField['title']->old_value);
        $this->assertSame('A2', $byField['title']->new_value);
        $this->assertSame('B', $byField['description']->old_value);
        $this->assertSame('B2', $byField['description']->new_value);
        $this->assertSame('bug', $byField['category']->old_value);
        $this->assertSame('feature', $byField['category']->new_value);
        $this->assertSame('low', $byField['priority']->old_value);
        $this->assertSame('high', $byField['priority']->new_value);

        // Všechny logy mají user_id = aktér (creator)
        foreach ($logs as $log) {
            $this->assertSame($this->user->id, $log->user_id);
        }
    }

    public function test_no_op_nezapise_audit_event(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Stejné',
            'category' => 'bug',
            'priority' => 'medium',
        ]);

        $this->actingAs($this->user);

        // PATCH se stejnými hodnotami → žádný audit event
        $this->patch("/tickets/{$ticket->uuid}", [
            'title' => 'Stejné',
            'category' => 'bug',
            'priority' => 'medium',
        ])->assertStatus(302);

        $this->assertSame(0, TicketAuditLog::where('ticket_id', $ticket->id)->count());
    }

    public function test_validation_max_length_title(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->patch("/tickets/{$ticket->uuid}", [
            'title' => Str::random(256),  // > 255 znaků
        ]);

        $response->assertStatus(302);  // back() s errors
        $response->assertSessionHasErrors(['title']);
    }

    public function test_validation_neplatna_kategorie(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->patch("/tickets/{$ticket->uuid}", [
            'category' => 'invalid_category',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['category']);
    }

    public function test_partial_update_jen_priority(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Neměnit',
            'description' => 'Také neměnit',
            'category' => 'bug',
            'priority' => 'low',
        ]);

        $this->actingAs($this->user);

        // Pouze priority — title, description, category nedotčeny.
        $this->patch("/tickets/{$ticket->uuid}", [
            'priority' => 'urgent',
        ])->assertStatus(302);

        $ticket->refresh();
        $this->assertSame('urgent', $ticket->priority->value);
        $this->assertSame('Neměnit', $ticket->title);
        $this->assertSame('Také neměnit', $ticket->description);
        $this->assertSame('bug', $ticket->category->value);

        // Audit má jen 1 záznam (priority).
        $logs = TicketAuditLog::where('ticket_id', $ticket->id)->get();
        $this->assertCount(1, $logs);
        $this->assertSame('priority', $logs->first()->field);
    }

    public function test_cross_tenant_user_dostane_403_nebo_404(): void
    {
        // User jiného tenanta zkouší editovat ticket v našem tenantu.
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Náš ticket',
        ]);

        $this->actingAs($this->otherUser);

        $response = $this->patch("/tickets/{$ticket->uuid}", [
            'title' => 'Hack cross-tenant',
        ]);

        $this->assertContains($response->status(), [403, 404]);

        $ticket->refresh();
        $this->assertSame('Náš ticket', $ticket->title);
    }
}
