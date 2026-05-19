<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webyashopy\Tickets\Enums\TicketCategory;
use Webyashopy\Tickets\Enums\TicketPriority;
use Webyashopy\Tickets\Enums\TicketStatus;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAttachment;
use Webyashopy\Tickets\Models\TicketAuditLog;
use Webyashopy\Tickets\Models\TicketComment;

// RefreshDatabase spustí balíčkové migrace (discoversMigrations) nad
// in-memory SQLite — ověřuje, že migrace projdou pod Orchestra Testbench.
uses(RefreshDatabase::class);

it('vytvoří tabulky tickets a ticket_* z balíčkových migrací', function () {
    expect(Schema::hasTable('tickets'))->toBeTrue()
        ->and(Schema::hasTable('ticket_attachments'))->toBeTrue()
        ->and(Schema::hasTable('ticket_audit_logs'))->toBeTrue()
        ->and(Schema::hasTable('ticket_comments'))->toBeTrue();
});

it('tabulka tickets má generický sloupec tenant_id (ne organization_id)', function () {
    expect(Schema::hasColumn('tickets', 'tenant_id'))->toBeTrue()
        ->and(Schema::hasColumn('tickets', 'organization_id'))->toBeFalse();
});

it('vytvoří ticket s nullable tenant_id (single-tenant default)', function () {
    $userId = createTestUser();

    $ticket = Ticket::create([
        'user_id' => $userId,
        'title' => 'Testovací ticket',
        'description' => 'Popis problému.',
        'category' => TicketCategory::BUG,
        'priority' => TicketPriority::HIGH,
        'status' => TicketStatus::OPEN,
    ]);

    expect($ticket->uuid)->not->toBeEmpty()
        ->and($ticket->tenant_id)->toBeNull()
        ->and($ticket->category)->toBe(TicketCategory::BUG)
        ->and($ticket->priority)->toBe(TicketPriority::HIGH)
        ->and($ticket->status)->toBe(TicketStatus::OPEN)
        ->and($ticket->isOpen())->toBeTrue()
        ->and($ticket->isClosed())->toBeFalse();
});

it('uloží ticket s tenant_id pro multi-tenant projekt', function () {
    $userId = createTestUser();

    $ticket = Ticket::create([
        'tenant_id' => 42,
        'user_id' => $userId,
        'title' => 'Multi-tenant ticket',
        'description' => 'Popis.',
        'category' => TicketCategory::FEATURE,
        'priority' => TicketPriority::LOW,
        'status' => TicketStatus::OPEN,
    ]);

    expect($ticket->fresh()->tenant_id)->toBe(42);
});

it('propojí ticket s komentáři, audit logy a přílohami', function () {
    $userId = createTestUser();
    $ticket = makeTicket($userId);

    $comment = TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => $userId,
        'body' => 'Komentář **markdown**.',
    ]);

    TicketAuditLog::create([
        'ticket_id' => $ticket->id,
        'user_id' => $userId,
        'field' => 'status',
        'old_value' => 'open',
        'new_value' => 'closed',
    ]);

    TicketAttachment::create([
        'ticket_id' => $ticket->id,
        'filename' => 'screenshot.png',
        'stored_path' => 'tickets/x/y.png',
        'mime_type' => 'image/png',
        'size_bytes' => 1024,
    ]);

    $ticket->refresh();

    expect($ticket->comments)->toHaveCount(1)
        ->and($ticket->auditLogs)->toHaveCount(1)
        ->and($ticket->attachments)->toHaveCount(1)
        ->and($comment->uuid)->not->toBeEmpty()
        ->and($comment->body_html)->toContain('<strong>markdown</strong>');
});

it('scopeForUser deleguje na NullTenantResolver (single-tenant pass-through)', function () {
    $userId = createTestUser();
    makeTicket($userId);
    makeTicket($userId);

    // NullTenantResolver vrací query beze změny → vidíme všechny tickety
    $user = (object) ['id' => $userId];
    $visible = Ticket::query()->forUser($user)->count();

    expect($visible)->toBe(2);
});

it('TicketComment::body_html sanitizuje nebezpečné HTML', function () {
    $userId = createTestUser();
    $ticket = makeTicket($userId);

    $comment = TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => $userId,
        'body' => "Text\n\n<script>alert(1)</script>",
    ]);

    expect($comment->body_html)->not->toContain('<script>');
});

/**
 * Vytvoří testovacího uživatele přímo v tabulce `users`.
 */
function createTestUser(): int
{
    return (int) DB::table('users')->insertGetId([
        'name' => 'Tester',
        'email' => 'tester' . uniqid() . '@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * Vytvoří minimální validní ticket pro daného uživatele.
 */
function makeTicket(int $userId): Ticket
{
    return Ticket::create([
        'user_id' => $userId,
        'title' => 'Ticket ' . uniqid(),
        'description' => 'Popis.',
        'category' => TicketCategory::OTHER,
        'priority' => TicketPriority::MEDIUM,
        'status' => TicketStatus::OPEN,
    ]);
}
