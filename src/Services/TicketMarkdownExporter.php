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
 *   ## Komentáře
 *
 *   ### 1. Jan Novák — 2026-05-06 15:10
 *
 *   > tělo komentáře jako blockquote
 *
 * Signed URL screenshotů má TTL `config('tickets.signed_url_ttl_hours')`,
 * default 24 h. Po expiraci jsou nedostupné — Claude WebFetch musí
 * stáhnout obrázky během této doby.
 *
 * Sekce „Komentáře" se vynechává úplně, pokud ticket žádné nemá (stejně
 * jako „Screenshoty" u ticketu bez příloh). Tělo komentáře je odsazené
 * jako blockquote (`> `), aby nadpis napsaný uživatelem uvnitř komentáře
 * (např. „# Ticket #…" nebo „## Popis") nerozbil strukturu exportu.
 */
final class TicketMarkdownExporter
{
    /**
     * Vrátí markdown reprezentaci ticketu.
     *
     * Předpokládá, že volající už eager-loadnul `attachments`, `creator`
     * a `comments.author` (není to tvrdá podmínka — `loadMissing` zajistí
     * konzistenci a zabrání N+1 při volání z hromadného exportu).
     */
    public function export(Ticket $ticket): string
    {
        $ticket->loadMissing(['creator', 'attachments', 'comments.author']);

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

        // Komentáře (chronologicky, vč. zavřených ticketů)
        if ($ticket->comments->isNotEmpty()) {
            $lines[] = '## Komentáře';
            $lines[] = '';

            foreach ($ticket->comments as $i => $comment) {
                $authorName = $comment->author?->name ?? '—';
                $commentedAt = $comment->created_at?->format('Y-m-d H:i') ?? '—';

                $lines[] = sprintf('### %d. %s — %s', $i + 1, $authorName, $commentedAt);
                $lines[] = '';

                // Tělo je raw Markdown od uživatele — odsazení jako blockquote,
                // ať nadpis napsaný v komentáři nerozbije strukturu exportu.
                foreach (explode("\n", (string) $comment->body) as $bodyLine) {
                    $lines[] = $bodyLine === '' ? '>' : '> ' . $bodyLine;
                }

                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }
}
