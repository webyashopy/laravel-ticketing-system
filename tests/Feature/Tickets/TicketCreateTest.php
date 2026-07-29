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
 *   - POST /tickets vrátí redirect ZPĚT (ne na detail ticketu)
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

        // Redirect zpět, ne na detail ticketu
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

    /**
     * Ticket se hlásí přes FAB z libovolné stránky host aplikace — po odeslání
     * musí uživatel zůstat tam, kde byl, ne skončit na detailu ticketu.
     */
    public function test_vytvoreni_ticketu_vrati_uzivatele_zpet_a_ne_na_detail(): void
    {
        $this->actingAs($this->user);

        $response = $this->from('/persons/123')->post('/tickets', [
            'title' => 'Modal se nezavírá',
            'description' => 'Po kliknutí na ESC zůstává modal otevřený.',
            'category' => 'bug',
            'priority' => 'high',
        ]);

        $ticket = Ticket::first();

        $response->assertRedirect('/persons/123');
        $this->assertStringNotContainsString(
            $ticket->uuid,
            (string) $response->headers->get('Location'),
        );
    }

    /**
     * Flash payload pro toast — frontend z něj skládá hlášku s odkazem
     * „Zobrazit" (viz `TicketCreateModal::showCreatedToast`).
     */
    public function test_vytvoreni_ticketu_flashne_data_pro_toast(): void
    {
        $this->actingAs($this->user);

        $response = $this->from('/persons/123')->post('/tickets', [
            'title' => 'Modal se nezavírá',
            'description' => 'Po kliknutí na ESC zůstává modal otevřený.',
            'category' => 'bug',
            'priority' => 'high',
        ]);

        $ticket = Ticket::first();

        $response->assertSessionHas('tickets_created');

        $created = session('tickets_created');
        $this->assertSame($ticket->id, $created['id']);
        $this->assertSame($ticket->uuid, $created['uuid']);
        $this->assertSame('Modal se nezavírá', $created['title']);
        // Odkaz míří na detail ticketu — toast na něj pustí až na kliknutí
        $this->assertStringContainsString($ticket->uuid, $created['url']);
    }

    /**
     * Bez Referer hlavičky (přímé volání endpointu) nesmí `back()` skončit
     * na kořenu host aplikace — fallback je seznam ticketů.
     */
    public function test_bez_referer_hlavicky_padne_redirect_na_seznam_ticketu(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/tickets', [
            'title' => 'Drobný překlep',
            'description' => 'Na úvodní stránce je překlep.',
            'category' => 'other',
            'priority' => 'low',
        ]);

        $response->assertRedirect(route('tickets.index'));
    }
}
