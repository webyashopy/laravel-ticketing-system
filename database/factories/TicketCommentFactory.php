<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketComment;

/**
 * Factory pro balíčkový model {@see TicketComment}.
 *
 * @extends Factory<TicketComment>
 */
class TicketCommentFactory extends Factory
{
    protected $model = TicketComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'ticket_id' => Ticket::factory(),
            'user_id' => 1,
            'body' => fake()->paragraph(2),
        ];
    }
}
