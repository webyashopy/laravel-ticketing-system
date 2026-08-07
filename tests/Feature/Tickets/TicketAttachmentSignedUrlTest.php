<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Services\TicketAttachmentStorage;

/**
 * Signed URL pro screenshot.
 *
 * Ověřuje:
 *   - Signed URL se správným podpisem → 200 + obsah souboru
 *   - Signed URL funguje i BEZ přihlášení (podpis je autorizace sám o sobě)
 *   - Manipulace s podpisem (?signature=invalid) → 403
 *   - Po uplynutí 24h → 403 (Carbon::setTestNow)
 */
class TicketAttachmentSignedUrlTest extends BaseTicketTest
{
    public function test_signed_url_plati_a_vraci_obrazek(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $storage = app(TicketAttachmentStorage::class);
        $attachment = $storage->store(
            UploadedFile::fake()->image('shot.png', 100, 100),
            $ticket,
        );

        // Signed URL z accessoru
        $signedUrl = $attachment->signed_url;
        $this->assertIsString($signedUrl);
        $this->assertStringContainsString('signature=', $signedUrl);
        $this->assertStringContainsString($ticket->uuid, $signedUrl);
        $this->assertStringContainsString($attachment->uuid, $signedUrl);

        // Volání přes celou URL — assertujeme 200
        $relativeUrl = parse_url($signedUrl, PHP_URL_PATH) . '?' . parse_url($signedUrl, PHP_URL_QUERY);
        $response = $this->get($relativeUrl);

        $response->assertStatus(200);
    }

    public function test_signed_url_funguje_bez_prihlaseni(): void
    {
        Storage::fake('local');

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $storage = app(TicketAttachmentStorage::class);
        $attachment = $storage->store(
            UploadedFile::fake()->image('shot.png', 100, 100),
            $ticket,
        );

        $signedUrl = $attachment->signed_url;
        $relativeUrl = parse_url($signedUrl, PHP_URL_PATH) . '?' . parse_url($signedUrl, PHP_URL_QUERY);

        // Nepřihlášený požadavek — markdown export (skill buguj) čte
        // screenshoty bez session, platný podpis musí stačit.
        $this->assertGuest();
        $this->get($relativeUrl)->assertStatus(200);
    }

    public function test_manipulace_se_signature_vraci_403(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $storage = app(TicketAttachmentStorage::class);
        $attachment = $storage->store(
            UploadedFile::fake()->image('shot.png', 100, 100),
            $ticket,
        );

        $signedUrl = $attachment->signed_url;

        // Vyrobíme path bez modifikace, ale s rozbitým podpisem
        $parsedUrl = parse_url($signedUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $queryParams['signature'] = str_repeat('a', 64); // očividně nevalidní
        $tampered = $parsedUrl['path'] . '?' . http_build_query($queryParams);

        $response = $this->get($tampered);

        // Laravel signed middleware vrací 403 při invalid signature
        $response->assertStatus(403);
    }

    public function test_po_24_hodinach_je_signed_url_expirovana(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $storage = app(TicketAttachmentStorage::class);
        $attachment = $storage->store(
            UploadedFile::fake()->image('shot.png', 100, 100),
            $ticket,
        );

        $signedUrl = $attachment->signed_url;
        $relativeUrl = parse_url($signedUrl, PHP_URL_PATH) . '?' . parse_url($signedUrl, PHP_URL_QUERY);

        // Travel +25h — TTL je 24h, takže vyprší
        Carbon::setTestNow(now()->addHours(25));

        $response = $this->get($relativeUrl);
        $response->assertStatus(403);

        Carbon::setTestNow(); // reset
    }
}
