<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Support\Facades\Mail;
use Webyashopy\Tickets\Mail\TicketsStaleDigestMail;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Stale notification cron.
 *
 * 2 stale tickety (>3 dny) → digest na centrální adresu.
 * Druhý běh: Mail::assertSent(...,1) — idempotence přes stale_email_sent_at.
 *
 * ODCHYLKA od původní implementace: původní verze měla výchozí digest adresu natvrdo.
 * Balíček vlastní výchozí adresu nemá — `stale_email_to` musí host nastavit
 *. Test ji proto v setUp() nastaví explicitně.
 */
class TicketStaleNotificationTest extends BaseTicketTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // Balíček nemá výchozí digest adresu — pro test ji nastavíme.
        config(['tickets.stale_email_to' => 'digest@example.com']);
    }

    public function test_command_posle_email_pro_stale_tickety_a_oznaci_je(): void
    {
        Mail::fake();

        // 2 stale tickety (vytvořené před 4 dny)
        $stale1 = Ticket::factory()->stale(4)->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);
        $stale2 = Ticket::factory()->stale(4)->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
            'status' => 'open',
        ]);

        // Nový ticket (1 den) — NESMÍ být v digestu
        $fresh = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'status' => 'open',
            'created_at' => now()->subDay(),
        ]);

        // Closed stale ticket — NESMÍ být v digestu (status=closed)
        $closedStale = Ticket::factory()->stale(5)->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'status' => 'closed',
        ]);

        $this->artisan('tickets:notify-stale')
            ->assertSuccessful();

        // Email odeslán právě 1× (digest, ne per-ticket)
        Mail::assertSent(TicketsStaleDigestMail::class, 1);

        // Email šel na centrální adresu z configu
        Mail::assertSent(TicketsStaleDigestMail::class, function ($mail) {
            return $mail->hasTo(config('tickets.stale_email_to'));
        });

        // Stale ticketům se nastavil stale_email_sent_at
        $stale1->refresh();
        $stale2->refresh();
        $this->assertNotNull($stale1->stale_email_sent_at);
        $this->assertNotNull($stale2->stale_email_sent_at);

        // Fresh i closed mají null
        $fresh->refresh();
        $closedStale->refresh();
        $this->assertNull($fresh->stale_email_sent_at);
        $this->assertNull($closedStale->stale_email_sent_at);
    }

    public function test_druhy_beh_uz_neposila_pro_uz_oznamene_tickety(): void
    {
        Mail::fake();

        Ticket::factory()->stale(4)->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);

        // První běh
        $this->artisan('tickets:notify-stale')->assertSuccessful();
        Mail::assertSent(TicketsStaleDigestMail::class, 1);

        // Druhý běh — žádné nové stale → žádný další email (fixed na 1×)
        $this->artisan('tickets:notify-stale')->assertSuccessful();
        Mail::assertSent(TicketsStaleDigestMail::class, 1);
    }

    public function test_zadne_stale_tickety_neposlou_email(): void
    {
        Mail::fake();

        // Jen fresh tickety (1 den)
        Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'status' => 'open',
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('tickets:notify-stale')->assertSuccessful();

        Mail::assertNotSent(TicketsStaleDigestMail::class);
    }
}
