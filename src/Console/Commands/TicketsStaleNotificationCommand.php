<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Console\Commands;

use Illuminate\Console\Command;
use Webyashopy\Tickets\Services\TicketStaleNotifier;
use Throwable;

/**
 * Denní cron příkaz: pošle digest email pro tickety Open déle než N dní
 *.
 *
 * Schedule:
 *   Registrováno v `TicketsServiceProvider` přes `callAfterResolving(Schedule)`
 *   — denně v 09:00, `withoutOverlapping`.
 *
 * Idempotence:
 *   Tickety, které už byly v digestu, mají `stale_email_sent_at`
 *   nastaveno → příští běh je vyfiltruje.
 */
class TicketsStaleNotificationCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tickets:notify-stale';

    /**
     * @var string
     */
    protected $description = 'Pošle digest email pro tickety Open déle než N dní (config tickets.stale_threshold_days)';

    public function handle(TicketStaleNotifier $notifier): int
    {
        $this->info('Hledám stale tickety…');

        try {
            $count = $notifier->notifyStaleTickets();
        } catch (Throwable $e) {
            $this->error('Stale notifikátor selhal: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($count === 0) {
            $this->info('Žádné stale tickety, e-mail se neposílá.');

            return self::SUCCESS;
        }

        $this->info("Odesláno {$count} stale notifikací.");

        return self::SUCCESS;
    }
}
