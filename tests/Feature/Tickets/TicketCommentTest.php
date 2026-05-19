<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAuditLog;
use Webyashopy\Tickets\Models\TicketComment;
use Webyashopy\Tickets\Services\TicketAuditService;
use Webyashopy\Tickets\Tests\Stubs\User;

/**
 * Feature testy pro lineární komentáře.
 *
 * Pokrytí:
 *   - Anti-IDOR (cross-tenant user nesmí komentovat)
 *   - Markdown sanitization (script/iframe stripped, safe tagy projdou)
 *   - Edit window 5 min (po té 403)
 *   - Delete: autor / superadmin / 403 pro cizí usery
 *   - Audit eventy comment_added / comment_deleted
 *   - body_html accessor
 */
class TicketCommentTest extends BaseTicketTest
{
    public function test_user_muze_komentovat_vlastni_tenant_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->post("/tickets/{$ticket->uuid}/comments", [
            'body' => 'První komentář.',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, TicketComment::count());

        $comment = TicketComment::first();
        $this->assertSame('První komentář.', $comment->body);
        $this->assertSame($this->user->id, $comment->user_id);
        $this->assertSame($ticket->id, $comment->ticket_id);
        $this->assertNotEmpty($comment->uuid);
    }

    public function test_cross_tenant_user_nemuze_komentovat(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // otherUser je v jiném tenantu
        $this->actingAs($this->otherUser);

        $response = $this->post("/tickets/{$ticket->uuid}/comments", [
            'body' => 'Cizí komentář.',
        ]);

        $response->assertForbidden();
        $this->assertSame(0, TicketComment::count());
    }

    public function test_superadmin_muze_komentovat_cross_tenant(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->otherTenantId,
            'user_id' => $this->otherUser->id,
        ]);

        $superAdmin = $this->superAdmin();
        $this->actingAs($superAdmin);

        $response = $this->post("/tickets/{$ticket->uuid}/comments", [
            'body' => 'Komentář superadmina.',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, TicketComment::count());
    }

    public function test_markdown_je_sanitized_strip_script_tag(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => '<script>alert(1)</script>Hello',
        ]);

        // Body uložen raw, ale body_html nesmí obsahovat funkční <script> tag.
        $html = $comment->body_html;
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<script ', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    public function test_markdown_je_sanitized_strip_iframe_a_on_handlery(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => '<iframe src="evil"></iframe><a href="javascript:alert(1)" onclick="hack()">link</a>',
        ]);

        $html = $comment->body_html;
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_markdown_renderuje_safe_html(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => "**tučně** a *kurzíva* + `code`\n\n- položka 1\n- položka 2",
        ]);

        $html = $comment->body_html;
        $this->assertStringContainsString('<strong>tučně</strong>', $html);
        $this->assertStringContainsString('<em>kurzíva</em>', $html);
        $this->assertStringContainsString('<code>code</code>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>položka 1</li>', $html);
    }

    public function test_body_html_accessor_vraci_html(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Hello world',
        ]);

        $this->assertNotEmpty($comment->body_html);
        $this->assertStringContainsString('<p>Hello world</p>', $comment->body_html);
    }

    public function test_creator_muze_editovat_do_5_min(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Original',
        ]);

        $this->actingAs($this->user);

        $response = $this->patch("/comments/{$comment->uuid}", [
            'body' => 'Editovaný text',
        ]);

        $response->assertRedirect();

        $comment->refresh();
        $this->assertSame('Editovaný text', $comment->body);
    }

    public function test_edit_window_po_6_minutach_je_403(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Original',
        ]);

        // Posuneme čas 6 minut dopředu — edit window vypršel
        $this->travel(6)->minutes();

        $this->actingAs($this->user);

        $response = $this->patch("/comments/{$comment->uuid}", [
            'body' => 'Pokus o pozdní edit',
        ]);

        $response->assertForbidden();

        $comment->refresh();
        $this->assertSame('Original', $comment->body);
    }

    public function test_jiny_user_nemuze_editovat_cizi_komentar(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $autor = $this->user;
        $jinyUserVeStejnemTenantu = User::factory()->create([
            'tenant_id' => $this->tenantId,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $autor->id,
            'body' => 'Komentář autora',
        ]);

        $this->actingAs($jinyUserVeStejnemTenantu);

        $response = $this->patch("/comments/{$comment->uuid}", [
            'body' => 'Pokus o cizí edit',
        ]);

        $response->assertForbidden();
    }

    public function test_autor_muze_smazat_vlastni_komentar(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Smažu sám.',
        ]);

        $this->actingAs($this->user);

        $response = $this->delete("/comments/{$comment->uuid}");

        $response->assertRedirect();
        $this->assertSame(0, TicketComment::count());
    }

    public function test_superadmin_muze_smazat_cizi_komentar(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Cizí komentář',
        ]);

        $superAdmin = $this->superAdmin();
        $this->actingAs($superAdmin);

        $response = $this->delete("/comments/{$comment->uuid}");

        $response->assertRedirect();
        $this->assertSame(0, TicketComment::count());
    }

    public function test_jiny_user_nemuze_smazat_cizi_komentar(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $autor = $this->user;
        $jinyUserVeStejnemTenantu = User::factory()->create([
            'tenant_id' => $this->tenantId,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $autor->id,
            'body' => 'Komentář autora',
        ]);

        $this->actingAs($jinyUserVeStejnemTenantu);

        $response = $this->delete("/comments/{$comment->uuid}");

        $response->assertForbidden();
        $this->assertSame(1, TicketComment::count());
    }

    public function test_audit_event_comment_added(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $this->post("/tickets/{$ticket->uuid}/comments", [
            'body' => 'Nový komentář',
        ]);

        $audit = TicketAuditLog::where('field', TicketAuditService::FIELD_COMMENT_ADDED)->first();
        $this->assertNotNull($audit, 'Audit event comment_added musí být zapsán');
        $this->assertSame($ticket->id, $audit->ticket_id);
        $this->assertSame($this->user->id, $audit->user_id);
        $this->assertNull($audit->old_value);

        // new_value je UUID komentáře
        $comment = TicketComment::first();
        $this->assertSame($comment->uuid, $audit->new_value);
    }

    public function test_audit_event_comment_deleted(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Komentář ke smazání',
        ]);

        $this->actingAs($this->user);

        $this->delete("/comments/{$comment->uuid}");

        $audit = TicketAuditLog::where('field', TicketAuditService::FIELD_COMMENT_DELETED)->first();
        $this->assertNotNull($audit, 'Audit event comment_deleted musí být zapsán');
        $this->assertSame($ticket->id, $audit->ticket_id);
        $this->assertSame($this->user->id, $audit->user_id);
        $this->assertSame('Komentář ke smazání', $audit->old_value);
        $this->assertNull($audit->new_value);
    }

    public function test_validation_min_1_znak(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->post("/tickets/{$ticket->uuid}/comments", [
            'body' => '',
        ]);

        $response->assertSessionHasErrors(['body']);
        $this->assertSame(0, TicketComment::count());
    }

    public function test_validation_max_5000_znaku(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->post("/tickets/{$ticket->uuid}/comments", [
            'body' => str_repeat('a', 5001),
        ]);

        $response->assertSessionHasErrors(['body']);
        $this->assertSame(0, TicketComment::count());
    }

    public function test_komentar_dostane_uuid_pri_vytvoreni(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Komentář',
        ]);

        $this->assertNotEmpty($comment->uuid);
        $this->assertSame(36, strlen($comment->uuid)); // UUID v4 délka
    }

    public function test_cascade_delete_komentaru_pri_smazani_ticketu(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Komentář 1',
        ]);
        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Komentář 2',
        ]);

        $this->assertSame(2, TicketComment::count());

        $ticket->delete();

        $this->assertSame(0, TicketComment::count());
    }
}
