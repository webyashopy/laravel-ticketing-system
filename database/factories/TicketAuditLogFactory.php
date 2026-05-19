<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAuditLog;
use Webyashopy\Tickets\Services\TicketAuditService;

/**
 * Factory pro balíčkový model {@see TicketAuditLog}.
 *
 * V původní implementaci samostatná factory pro audit log neexistovala (eventy vznikaly
 * vždy přes `TicketAuditService`). V balíčku ji doplňujeme pro úplnost
 * a snadné setupy testů, které potřebují existující audit záznam.
 *
 * @extends Factory<TicketAuditLog>
 */
class TicketAuditLogFactory extends Factory
{
    protected $model = TicketAuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => 1,
            'field' => TicketAuditService::FIELD_STATUS,
            'old_value' => 'open',
            'new_value' => 'closed',
        ];
    }
}
