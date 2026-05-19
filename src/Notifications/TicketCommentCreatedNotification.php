<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Notifikace: nový komentář u ticketu.
 *
 * Příjemci:
 *  - autor ticketu (creator) — User instance host aplikace
 *  - centrální adresa přes `config('tickets.stale_email_to')` — anon route
 *
 * Vylučuje aktora (žádný self-notification) — řeší TicketNotificationDispatcher.
 *
 * Duck-typing $comment:
 *  - očekává property `uuid` (string)
 *  - očekává property `body` (string)
 *
 * Duck-typing $actor:
 *  - balíček User model nezná napřímo — `$actor` je typovaný
 *    volně (`object`), potřebné jen `->name` / `->email`.
 *
 * Database kanál je aktivní jen pokud je notifiable instancí host User
 * modelu (`config('tickets.models.user_model')`) — anon mail route ho nemá.
 */
class TicketCommentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Ticket  $ticket  Ticket, ke kterému komentář patří
     * @param  object  $comment  TicketComment instance (uuid, body)
     * @param  object  $actor  Uživatel, který komentář napsal
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly object $comment,
        public readonly object $actor,
    ) {}

    /**
     * Database channel jen pro host User notifiable, mail pro všechny.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($this->isHostUser($notifiable)) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $uuidShort = $this->shortUuid();
        $actionUrl = $this->actionUrl();
        $appName = (string) config('app.name', 'Tickets');

        return (new MailMessage())
            ->subject("[{$appName}] Ticket #{$uuidShort}: nový komentář")
            ->markdown('tickets::emails.tickets.comment-created', [
                'ticket' => $this->ticket,
                'comment' => $this->comment,
                'actor' => $this->actor,
                'actionUrl' => $actionUrl,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ticket_comment_created',
            'ticket_uuid' => $this->ticket->uuid,
            'ticket_title' => $this->ticket->title,
            'actor_name' => $this->actor->name,
            'title' => "Nový komentář od {$this->actor->name}",
            'body' => Str::limit((string) ($this->comment->body ?? ''), 200),
            'action_url' => $this->actionUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Action URL s anchor na konkrétní komentář.
     */
    private function actionUrl(): string
    {
        $commentUuid = (string) ($this->comment->uuid ?? '');

        return "/tickets/{$this->ticket->uuid}#comment-{$commentUuid}";
    }

    /**
     * Prvních 8 znaků UUID pro subject.
     */
    private function shortUuid(): string
    {
        return substr((string) $this->ticket->uuid, 0, 8);
    }

    /**
     * Je notifiable instancí host User modelu (z configu)?
     *
     * Anon mail route (`Notification::route('mail', ...)`) tímto neprojde
     * — database kanál se pro ni neaktivuje.
     */
    private function isHostUser(object $notifiable): bool
    {
        $userModel = (string) config('tickets.models.user_model', 'App\\Models\\User');

        return class_exists($userModel) && $notifiable instanceof $userModel;
    }
}
