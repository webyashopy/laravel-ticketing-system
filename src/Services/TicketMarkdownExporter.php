<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Services;

use Webyashopy\Tickets\Models\Ticket;

/**
 * Generuje markdown export ticketu pro Claude Code.
 *
 * Output formát (Claude-ready):
 *
 *   # Ticket #{uuid}: {title}
 *
 *   **Kategorie:** bug
 *   **Priorita:** high
 *   **Stav:** Otevřený
 *   **Vytvořeno:** 2026-05-06 14:23 (Jan Novák)
 *   **URL stránky:** /example/page
 *   **Browser:** {user_agent}
 *   **Viewport:** 1920x1080
 *
 *   ## Popis
 *
 *   {description}
 *
 *   ## Screenshoty
 *
 *   1. ![screenshot-1]({signed_url_1})
 *   2. ![screenshot-2]({signed_url_2})
 *
 * Signed URL screenshotů má TTL `config('tickets.signed_url_ttl_hours')`,
 * default 24 h. Po expiraci jsou nedostupné — Claude WebFetch musí
 * stáhnout obrázky během této doby.
 */
final class TicketMarkdownExporter
{
    /**
     * Vrátí markdown reprezentaci ticketu.
     *
     * Předpokládá, že volající už eager-loadnul `attachments` a `creator`
     * (není to tvrdá podmínka — `loadMissing` zajistí konzistenci).
     */
    public function export(Ticket $ticket): string
    {
        $ticket->loadMissing(['creator', 'attachments']);

        $lines = [];

        // Záhlaví
        $lines[] = sprintf('# Ticket #%s: %s', $ticket->uuid, $ticket->title);
        $lines[] = '';

        // Metadata
        $createdAt = $ticket->created_at?->format('Y-m-d H:i') ?? '—';
        $creatorName = $ticket->creator?->name ?? '—';

        $lines[] = sprintf('**Kategorie:** %s', $ticket->category->label());
        $lines[] = sprintf('**Priorita:** %s', $ticket->priority->label());
        $lines[] = sprintf('**Stav:** %s', $ticket->status->label());
        $lines[] = sprintf('**Vytvořeno:** %s (%s)', $createdAt, $creatorName);

        if ($ticket->page_url !== null && $ticket->page_url !== '') {
            $lines[] = sprintf('**URL stránky:** %s', $ticket->page_url);
        }

        if ($ticket->viewport !== null && $ticket->viewport !== '') {
            $lines[] = sprintf('**Viewport:** %s', $ticket->viewport);
        }

        if ($ticket->user_agent !== null && $ticket->user_agent !== '') {
            $lines[] = sprintf('**Browser:** %s', $ticket->user_agent);
        }

        if ($ticket->isClosed() && $ticket->closed_at !== null) {
            $closerName = $ticket->closer?->name ?? '—';
            $lines[] = sprintf(
                '**Uzavřeno:** %s (%s)',
                $ticket->closed_at->format('Y-m-d H:i'),
                $closerName,
            );
        }

        $lines[] = '';

        // Popis
        $lines[] = '## Popis';
        $lines[] = '';
        $lines[] = $ticket->description;
        $lines[] = '';

        // Screenshoty (s signed URL)
        if ($ticket->attachments->isNotEmpty()) {
            $lines[] = '## Screenshoty';
            $lines[] = '';

            foreach ($ticket->attachments as $i => $attachment) {
                $alt = sprintf('screenshot-%d', $i + 1);
                $lines[] = sprintf(
                    '%d. ![%s](%s)',
                    $i + 1,
                    $alt,
                    $attachment->signed_url,
                );
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
