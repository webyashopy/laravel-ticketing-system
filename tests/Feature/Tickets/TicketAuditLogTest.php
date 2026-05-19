<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAuditLog;
use Webyashopy\Tickets\Services\TicketAuditService;

/**
 * Audit log služba pro tickety.
 */
class TicketAuditLogTest extends BaseTicketTest
{
    private TicketAuditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TicketAuditService::class);
    }

    public function test_record_zapise_event_s_old_a_new_value(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $event = $this->service->record(
            $ticket,
            $this->user,
            TicketAuditService::FIELD_PRIORITY,
            'low',
            'high',
        );

        $this->assertNotNull($event);
        $this->assertSame($ticket->id, $event->ticket_id);
        $this->assertSame($this->user->id, $event->user_id);
        $this->assertSame('priority', $event->field);
        $this->assertSame('low', $event->old_value);
        $this->assertSame('high', $event->new_value);
        $this->assertSame(1, TicketAuditLog::count());
    }

    public function test_no_op_zmena_se_neulozi(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $event = $this->service->record(
            $ticket,
            $this->user,
            TicketAuditService::FIELD_TITLE,
            'Stejný název',
            'Stejný název',
        );

        $this->assertNull($event);
        $this->assertSame(0, TicketAuditLog::count());
    }

    public function test_record_many_zapise_jen_zmenena_pole(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $written = $this->service->recordMany($ticket, $this->user, [
            ['field' => TicketAuditService::FIELD_TITLE, 'old' => 'Starý', 'new' => 'Nový'],
            ['field' => TicketAuditService::FIELD_PRIORITY, 'old' => 'low', 'new' => 'low'], // no-op
            ['field' => TicketAuditService::FIELD_CATEGORY, 'old' => 'bug', 'new' => 'feature'],
        ]);

        $this->assertCount(2, $written);
        $this->assertSame(2, TicketAuditLog::count());
    }

    public function test_neznameno_pole_vyhodi_invalid_argument(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->record(
            $ticket,
            $this->user,
            'malicious_field',
            'a',
            'b',
        );
    }

    public function test_cascade_delete_pri_smazani_ticketu(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->service->record($ticket, $this->user, TicketAuditService::FIELD_TITLE, 'a', 'b');
        $this->service->record($ticket, $this->user, TicketAuditService::FIELD_PRIORITY, 'low', 'high');

        $this->assertSame(2, TicketAuditLog::count());

        $ticket->delete();

        $this->assertSame(0, TicketAuditLog::count());
    }

    public function test_audit_log_relation_na_ticketu_funguje(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        $this->service->record($ticket, $this->user, TicketAuditService::FIELD_TITLE, 'a', 'b');
        $this->service->record($ticket, $this->user, TicketAuditService::FIELD_PRIORITY, 'low', 'high');

        $ticket->refresh();

        $this->assertCount(2, $ticket->auditLogs);
        // Chronologicky (oldest first per relation orderBy)
        $this->assertSame('title', $ticket->auditLogs->first()->field);
    }

    public function test_user_null_je_povoleny(): void
    {
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
        ]);

        // Systémový event bez user (např. cron)
        $event = $this->service->record(
            $ticket,
            null,
            TicketAuditService::FIELD_STATUS,
            'open',
            'closed',
        );

        $this->assertNotNull($event);
        $this->assertNull($event->user_id);
    }
}
