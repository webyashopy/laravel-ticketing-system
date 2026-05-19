<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Enums;

/**
 * Priorita ticketu.
 */
enum TicketPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    /**
     * Český popisek priority.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Nízká',
            self::MEDIUM => 'Střední',
            self::HIGH => 'Vysoká',
            self::URGENT => 'Urgentní',
        };
    }

    /**
     * DaisyUI badge class pro priority badge v listu / detailu.
     */
    public function daisyBadgeClass(): string
    {
        return match ($this) {
            self::LOW => 'badge-info',
            self::MEDIUM => 'badge-neutral',
            self::HIGH => 'badge-warning',
            self::URGENT => 'badge-error',
        };
    }

    /**
     * Číselná hodnota pro řazení (urgent první).
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::URGENT => 4,
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1,
        };
    }

    /**
     * Pole hodnot (pro validaci).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Pole pro frontend select.
     *
     * @return list<array{value: string, label: string, badge: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'badge' => $case->daisyBadgeClass(),
        ], self::cases());
    }
}
