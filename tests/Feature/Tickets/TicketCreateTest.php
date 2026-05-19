<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAttachment;

/**
 * Happy path: vytvoření ticketu s 2 přílohami.
 *
 * Ověřuje:
 *   - POST /tickets vrátí redirect na detail
 *   - V DB existuje 1 ticket s tenant_id + user_id
 *   - V DB existují 2 attachments
 *   - Soubory jsou skutečně uložené ve storage/app/tickets/{ticket_uuid}/
 */
class TicketCreateTest extends BaseTicketTest
{
    public function test_user_muze_vytvorit_ticket_se_dvema_prilohami(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $screenshot1 = UploadedFile::fake()->image('chyba-1.png', 100, 100);
        $screenshot2 = UploadedFile::fake()->image('chyba-2.png', 100, 100);

        $response = $this->post('/tickets', [
            'title' => 'Modal se nezavírá',
            'description' => 'Po kliknutí na ESC zůstává modal otevřený.',
            'category' => 'bug',
            'priority' => 'high',
            'page_url' => '/persons/123',
            'viewport' => '1920x1080',
            'user_agent' => 'Mozilla/5.0 (Test)',
            'attachments' => [$screenshot1, $screenshot2],
        ]);

        // Redirect na detail nově vzniklého ticketu
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // DB: 1 ticket
        $this->assertSame(1, Ticket::count());
        $ticket = Ticket::first();
        // tenant_id se odvozuje z TicketTenantResolver::tenantIdFor()
        $this->assertSame($this->tenantId, $ticket->tenant_id);
        $this->assertSame($this->user->id, $ticket->user_id);
        $this->assertSame('Modal se nezavírá', $ticket->title);
        $this->assertSame('open', $ticket->status->value);

        // DB: 2 attachments
        $this->assertSame(2, TicketAttachment::count());
        $attachments = $ticket->attachments;
        $this->assertCount(2, $attachments);

        foreach ($attachments as $attachment) {
            // Path obsahuje ticket UUID
            $this->assertStringContainsString("tickets/{$ticket->uuid}/", $attachment->stored_path);
            // Soubor opravdu existuje
            Storage::disk('local')->assertExists($attachment->stored_path);
            // Mime z whitelistu
            $this->assertSame('image/png', $attachment->mime_type);
            $this->assertGreaterThan(0, $attachment->size_bytes);
        }
    }

    public function test_ticket_lze_vytvorit_i_bez_priloh(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $response = $this->post('/tickets', [
            'title' => 'Drobný překlep',
            'description' => 'Na úvodní stránce je překlep.',
            'category' => 'other',
            'priority' => 'low',
        ]);

        $response->assertStatus(302);
        $this->assertSame(1, Ticket::count());
        $this->assertSame(0, TicketAttachment::count());
    }
}
