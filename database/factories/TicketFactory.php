<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Enums\TicketCategory;
use Webyashopy\Tickets\Enums\TicketPriority;
use Webyashopy\Tickets\Enums\TicketStatus;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Factory pro balíčkový model {@see Ticket}.
 *
 * Oproti původní factory: `organization_id` → `tenant_id` (nullable, default
 * `null` = single-tenant; multi-tenant testy hodnotu nastaví explicitně).
 * `user_id` neukazuje na pevný User model — host User dodá test přes state.
 *
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => null,
            'user_id' => 1,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'category' => fake()->randomElement(TicketCategory::values()),
            'priority' => fake()->randomElement(TicketPriority::values()),
            'status' => TicketStatus::OPEN->value,
            'page_url' => '/' . fake()->slug(2),
            'viewport' => '1920x1080',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'closed_at' => null,
            'closed_by_user_id' => null,
            'stale_email_sent_at' => null,
        ];
    }

    /**
     * Uzavřený ticket — auditní pole vyplněna.
     *
     * @param  object|null  $closer  Uživatel, který ticket zavřel (atribut `id`).
     */
    public function closed(?object $closer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::CLOSED->value,
            'closed_at' => now(),
            'closed_by_user_id' => $closer?->id ?? $attributes['user_id'] ?? 1,
        ]);
    }

    /**
     * Stale ticket — vytvořený před více dny než threshold.
     */
    public function stale(int $daysAgo = 4): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays($daysAgo),
            'updated_at' => now()->subDays($daysAgo),
        ]);
    }

    /**
     * Konkrétní kategorie / priorita.
     */
    public function withCategory(TicketCategory $category): static
    {
        return $this->state(fn () => ['category' => $category->value]);
    }

    public function withPriority(TicketPriority $priority): static
    {
        return $this->state(fn () => ['priority' => $priority->value]);
    }
}
