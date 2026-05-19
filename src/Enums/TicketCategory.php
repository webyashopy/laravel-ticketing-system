<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Enums;

/**
 * Kategorie ticketu.
 *
 * Volíme z plochého seznamu, žádné podkategorie v MVP.
 */
enum TicketCategory: string
{
    case BUG = 'bug';
    case FEATURE = 'feature';
    case QUESTION = 'question';
    case OTHER = 'other';

    /**
     * Český popisek pro UI / select option.
     */
    public function label(): string
    {
        return match ($this) {
            self::BUG => 'Bug / Chyba',
            self::FEATURE => 'Návrh funkce',
            self::QUESTION => 'Dotaz',
            self::OTHER => 'Jiné',
        };
    }

    /**
     * DaisyUI badge class pro vizuální odlišení v listu.
     */
    public function daisyBadgeClass(): string
    {
        return match ($this) {
            self::BUG => 'badge-error',
            self::FEATURE => 'badge-success',
            self::QUESTION => 'badge-info',
            self::OTHER => 'badge-ghost',
        };
    }

    /**
     * Vrátí všechny hodnoty jako pole (pro validaci).
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
