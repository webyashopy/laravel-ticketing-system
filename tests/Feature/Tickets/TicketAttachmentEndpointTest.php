<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAttachment;
use Webyashopy\Tickets\Models\TicketAuditLog;
use Webyashopy\Tickets\Services\TicketAuditService;

/**
 * Endpointy pro add/remove příloh existujícího ticketu
 *.
 *
 * Pokrývá:
 *   - autorizaci (tvůrce / superadmin / jiný user → 403)
 *   - per-ticket limit (21. příloha → withErrors)
 *   - anti-IDOR cross-ticket UUID guessing (→ 404)
 *   - audit log eventy attachment_added / attachment_removed
 */
class TicketAttachmentEndpointTest extends BaseTicketTest
{
    public function test_creator_muze_pridat_prilohu(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $file = UploadedFile::fake()->image('extra-shot.png', 100, 100);

        $response = $this->post("/tickets/{$ticket->uuid}/attachments", [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame(1, $ticket->attachments()->count());
    }

    public function test_superadmin_muze_pridat_libovolneho_ticketu(): void
    {
        Storage::fake('local');

        // Ticket patří uživateli z jiného tenanta, superadmin v tenantu 1
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->superAdmin($this->tenantId));

        $file = UploadedFile::fake()->image('admin-shot.png', 100, 100);

        $response = $this->post("/tickets/{$ticket->uuid}/attachments", [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame(1, $ticket->attachments()->count());
    }

    public function test_pristup_21_priloha_se_odmitne(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Předem vytvoříme 20 attachmentů (max_attachments=20)
        TicketAttachment::factory()->count(20)->create([
            'ticket_id' => $ticket->id,
        ]);

        $file = UploadedFile::fake()->image('twenty-first.png', 100, 100);

        $response = $this->post("/tickets/{$ticket->uuid}/attachments", [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['file']);
        // Žádná nová příloha — pořád 20
        $this->assertSame(20, $ticket->attachments()->count());
    }

    public function test_creator_muze_smazat_prilohu(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $response = $this->delete("/tickets/{$ticket->uuid}/attachments/{$attachment->uuid}");

        $response->assertRedirect();
        $this->assertSame(0, $ticket->attachments()->count());
    }

    public function test_cross_ticket_attachment_uuid_404(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        // Ticket A — patří našemu uživateli
        $ticketA = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Ticket B — taky náš user, ale jiný ticket
        $ticketB = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Attachment patří B, ale URL ukazuje na A — cross-ticket guessing
        $attachmentB = TicketAttachment::factory()->create([
            'ticket_id' => $ticketB->id,
        ]);

        $response = $this->delete("/tickets/{$ticketA->uuid}/attachments/{$attachmentB->uuid}");

        $response->assertStatus(404);
        // Attachment v B zůstal nedotčený
        $this->assertSame(1, $ticketB->attachments()->count());
    }

    public function test_audit_event_attachment_added(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $file = UploadedFile::fake()->image('audit-add.png', 100, 100);

        $this->post("/tickets/{$ticket->uuid}/attachments", [
            'file' => $file,
        ]);

        // Najdi audit event pro tento ticket
        $event = TicketAuditLog::query()
            ->where('ticket_id', $ticket->id)
            ->where('field', TicketAuditService::FIELD_ATTACHMENT_ADDED)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($this->user->id, $event->user_id);
        $this->assertNull($event->old_value);
        $this->assertSame('audit-add.png', $event->new_value);
    }

    public function test_audit_event_attachment_removed(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'filename' => 'to-remove.png',
        ]);

        $this->delete("/tickets/{$ticket->uuid}/attachments/{$attachment->uuid}");

        $event = TicketAuditLog::query()
            ->where('ticket_id', $ticket->id)
            ->where('field', TicketAuditService::FIELD_ATTACHMENT_REMOVED)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($this->user->id, $event->user_id);
        $this->assertSame('to-remove.png', $event->old_value);
        $this->assertNull($event->new_value);
    }

    public function test_other_user_nemuze_pridat(): void
    {
        Storage::fake('local');

        // Ticket patří našemu primary userovi
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Útočí cizí user z jiného tenanta
        $this->actingAs($this->otherUser);

        $file = UploadedFile::fake()->image('hostile.png', 100, 100);

        $response = $this->post("/tickets/{$ticket->uuid}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(403);
        $this->assertSame(0, $ticket->attachments()->count());
    }

    public function test_other_user_nemuze_smazat(): void
    {
        Storage::fake('local');

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $this->actingAs($this->otherUser);

        $response = $this->delete("/tickets/{$ticket->uuid}/attachments/{$attachment->uuid}");

        $response->assertStatus(403);
        $this->assertSame(1, $ticket->attachments()->count());
    }

    public function test_svg_pri_add_je_odmitnut_kvuli_xss(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $svg = UploadedFile::fake()->createWithContent(
            'malicious.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script></svg>',
        );

        $response = $this->post("/tickets/{$ticket->uuid}/attachments", [
            'file' => $svg,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertSame(0, $ticket->attachments()->count());
    }
}
