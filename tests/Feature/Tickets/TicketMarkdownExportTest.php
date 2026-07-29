<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAttachment;
use Webyashopy\Tickets\Models\TicketComment;

/**
 * Markdown export endpoint pro Claude Code.
 *
 * GET /api/tickets/{uuid}/export.md vrátí:
 *   - text/markdown content-type
 *   - markdown obsahuje title, description, signed URL screenshotů
 *   - anti-IDOR: cizí ticket → 403
 */
class TicketMarkdownExportTest extends BaseTicketTest
{
    public function test_markdown_export_vraci_correct_content_type(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Modal nezavírá',
            'description' => 'Po stisknutí ESC modal zůstává.',
            'page_url' => '/persons/abc-123',
            'viewport' => '1920x1080',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0)',
        ]);

        $this->actingAs($this->user);

        $response = $this->get("/api/tickets/{$ticket->uuid}/export.md");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    }

    public function test_markdown_obsahuje_title_description_a_metadata(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'title' => 'Modal nezavírá',
            'description' => 'Po stisknutí ESC modal zůstává.',
            'page_url' => '/persons/abc-123',
            'viewport' => '1920x1080',
            'user_agent' => 'Mozilla/5.0 (Test)',
        ]);

        $this->actingAs($this->user);

        $response = $this->get("/api/tickets/{$ticket->uuid}/export.md");

        $response->assertStatus(200);
        $body = $response->getContent();

        $this->assertStringContainsString('# Ticket', $body);
        $this->assertStringContainsString($ticket->uuid, $body);
        $this->assertStringContainsString('Modal nezavírá', $body);
        $this->assertStringContainsString('Po stisknutí ESC', $body);
        $this->assertStringContainsString('/persons/abc-123', $body);
        $this->assertStringContainsString('1920x1080', $body);
        $this->assertStringContainsString('Mozilla/5.0', $body);
        $this->assertStringContainsString('## Popis', $body);
    }

    public function test_markdown_obsahuje_signed_url_screenshots(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Přímo v DB (bez fyzického storage — exporter signuje URL bez fs check)
        TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'filename' => 'screenshot-1.png',
            'stored_path' => "tickets/{$ticket->uuid}/foo.png",
        ]);
        TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'filename' => 'screenshot-2.png',
            'stored_path' => "tickets/{$ticket->uuid}/bar.png",
        ]);

        $this->actingAs($this->user);

        $response = $this->get("/api/tickets/{$ticket->uuid}/export.md");

        $response->assertStatus(200);
        $body = $response->getContent();

        $this->assertStringContainsString('## Screenshoty', $body);
        // Markdown image syntax
        $this->assertStringContainsString('![screenshot-1]', $body);
        $this->assertStringContainsString('![screenshot-2]', $body);
        // Signed URL obsahuje signature param
        $this->assertStringContainsString('signature=', $body);
    }

    public function test_markdown_neobsahuje_sekci_komentare_kdyz_zadne_nejsou(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get("/api/tickets/{$ticket->uuid}/export.md");

        $response->assertStatus(200);
        $this->assertStringNotContainsString('## Komentáře', $response->getContent());
    }

    public function test_markdown_obsahuje_komentare_s_autorem_datem_a_blockquote_telem(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'První odpověď na ticket.',
            'created_at' => now()->subHour(),
        ]);

        // Tělo obsahuje nadpis — musí zůstat jako součást blockquote, ne
        // aby rozbilo strukturu exportu (viz TASK-472).
        TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => "## Popis\nUpřesnění s vlastním nadpisem uvnitř.",
            'created_at' => now(),
        ]);

        $this->actingAs($this->user);

        $response = $this->get("/api/tickets/{$ticket->uuid}/export.md");

        $response->assertStatus(200);
        $body = $response->getContent();

        $this->assertStringContainsString('## Komentáře', $body);
        $this->assertStringContainsString($this->user->name, $body);
        $this->assertStringContainsString('> První odpověď na ticket.', $body);
        $this->assertStringContainsString('> ## Popis', $body);
        $this->assertStringContainsString('> Upřesnění s vlastním nadpisem uvnitř.', $body);

        // Chronologické pořadí (comments() je orderBy created_at) — první
        // komentář se v textu objeví dřív než druhý.
        $this->assertLessThan(
            strpos($body, '> ## Popis'),
            strpos($body, '> První odpověď na ticket.'),
        );
    }

    public function test_anti_idor_export_ciziho_tenanta_403(): void
    {
        $foreignTicket = Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get("/api/tickets/{$foreignTicket->uuid}/export.md");

        $this->assertContains($response->status(), [403, 404]);
    }
}
