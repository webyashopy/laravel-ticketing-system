<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Enums;

/**
 * Stav ticketu.
 *
 * MVP: jen Open / Closed (žádné mezistavy).
 */
enum TicketStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';

    /**
     * Český popisek stavu.
     */
    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Otevřený',
            self::CLOSED => 'Uzavřený',
        };
    }

    /**
     * DaisyUI badge class pro vizuální odlišení.
     */
    public function daisyBadgeClass(): string
    {
        return match ($this) {
            self::OPEN => 'badge-success',
            self::CLOSED => 'badge-ghost',
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
     * Pole pro frontend select / filter.
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
