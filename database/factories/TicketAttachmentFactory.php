<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAttachment;

/**
 * Factory pro balíčkový model {@see TicketAttachment}.
 *
 * @extends Factory<TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    protected $model = TicketAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'ticket_id' => Ticket::factory(),
            'filename' => 'screenshot-' . fake()->word() . '.png',
            'stored_path' => 'tickets/dummy-ticket-uuid/' . $uuid . '.png',
            'mime_type' => 'image/png',
            'size_bytes' => fake()->numberBetween(1024, 1024 * 100),
        ];
    }
}
