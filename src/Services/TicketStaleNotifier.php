<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webyashopy\Tickets\Enums\TicketStatus;
use Webyashopy\Tickets\Mail\TicketsStaleDigestMail;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Detekuje stale tickety (Open déle než N dní) a posílá denní digest
 * e-mail.
 *
 * Idempotence:
 *   Tickety, které už jednou byly v digestu, mají `stale_email_sent_at`
 *   nastaveno → další běh je vyfiltruje. Jeden ticket = jedno
 *   notifikační e-mail. Pokud cron několikrát selže během dne,
 *   nezopakuje se.
 *
 * Příjemce:
 *   `config('tickets.stale_email_to')`. Pokud není vyplněn (balíček
 *   nemá vlastní výchozí adresu), digest se tiše přeskočí — host
 *   aplikace musí adresu explicitně nastavit přes env `TICKETS_STALE_EMAIL`.
 *
 * Mailer:
 *   Standardní `Mail::to(...)` na default channel ze serveru — cron běží
 *   bez authenticated user.
 *
 * POZN.: oproti původní verzi se NEEAGER-LOADuje relace `organization`
 * — balíček tenant model nezná. Digest tabulka tenant sloupec
 * neobsahuje.
 */
final class TicketStaleNotifier
{
    /**
     * Spustí stale-detection a pošle digest. Vrátí počet ticketů zařazených
     * do digestu (0 = nikdo nebyl stale, e-mail se neposílá).
     */
    public function notifyStaleTickets(): int
    {
        $thresholdDays = (int) config('tickets.stale_threshold_days', 3);
        $recipient = (string) config('tickets.stale_email_to', '');
        $cutoff = now()->subDays($thresholdDays);

        // Bez nakonfigurovaného příjemce digest tiše přeskočíme — balíček
        // nemá vlastní výchozí adresu.
        if (trim($recipient) === '') {
            Log::info('TicketStaleNotifier: stale_email_to není nastaven, digest přeskočen.');

            return 0;
        }

        // Najdeme staleové, eager-loadneme creator pro digest tabulku.
        $staleTickets = Ticket::query()
            ->where('status', TicketStatus::OPEN->value)
            ->where('created_at', '<', $cutoff)
            ->whereNull('stale_email_sent_at')
            ->with(['creator:id,name,email'])
            ->orderBy('created_at')
            ->get();

        if ($staleTickets->isEmpty()) {
            return 0;
        }

        $count = $staleTickets->count();

        Log::info('TicketStaleNotifier: posílám digest', [
            'recipient' => $recipient,
            'tickets_count' => $count,
            'threshold_days' => $thresholdDays,
        ]);

        // Server channel — fixní adresa z configu.
        Mail::to($recipient)->send(new TicketsStaleDigestMail(
            tickets: $staleTickets,
            thresholdDays: $thresholdDays,
        ));

        // Idempotence: označit všechny zaslané tickety.
        Ticket::query()
            ->whereIn('id', $staleTickets->pluck('id')->all())
            ->update(['stale_email_sent_at' => now()]);

        return $count;
    }
}
