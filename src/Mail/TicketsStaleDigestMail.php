<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Denní digest stale ticketů.
 *
 * Content view: `tickets::emails.tickets.stale-digest` (markdown mailable).
 * Volá ho `TicketStaleNotifier::notifyStaleTickets()` přes
 * `Mail::to($recipient)->send($mailable)`.
 */
class TicketsStaleDigestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  Collection<int, \Webyashopy\Tickets\Models\Ticket>  $tickets  Stale tickety s eager-loaded creatorem.
     * @param  int  $thresholdDays  Práh stáří v dnech (z configu).
     */
    public function __construct(
        public Collection $tickets,
        public int $thresholdDays,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->tickets->count();
        $appName = (string) config('app.name', 'Tickets');

        return new Envelope(
            subject: "[{$appName}] {$count} ticketů čeká více než {$this->thresholdDays} dní",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'tickets::emails.tickets.stale-digest',
            with: [
                'tickets' => $this->tickets,
                'thresholdDays' => $this->thresholdDays,
                'appUrl' => (string) config('app.url'),
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
