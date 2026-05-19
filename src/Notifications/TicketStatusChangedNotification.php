<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Notifikace: změna stavu ticketu — closed nebo reopened.
 *
 * Příjemci stejní jako u TicketCommentCreatedNotification — řeší dispatcher.
 *
 * Duck-typing $actor: balíček User model nezná napřímo, `$actor`
 * je typovaný volně (`object`), potřebné jen `->name`.
 */
class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const ACTION_CLOSED = 'closed';

    public const ACTION_REOPENED = 'reopened';

    /**
     * @param  Ticket  $ticket  Ticket, který změnil status
     * @param  string  $action  'closed' nebo 'reopened'
     * @param  object  $actor  Uživatel, který akci provedl
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $action,
        public readonly object $actor,
    ) {
        if (! in_array($action, [self::ACTION_CLOSED, self::ACTION_REOPENED], true)) {
            throw new InvalidArgumentException("Neplatná akce: {$action}");
        }
    }

    /**
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
        $actionCs = $this->actionCs();
        $appName = (string) config('app.name', 'Tickets');

        return (new MailMessage())
            ->subject("[{$appName}] Ticket #{$uuidShort}: {$actionCs}")
            ->markdown('tickets::emails.tickets.status-changed', [
                'ticket' => $this->ticket,
                'action' => $this->action,
                'actionCs' => $actionCs,
                'actor' => $this->actor,
                'actionUrl' => $this->actionUrl(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $actionCs = $this->actionCs();

        return [
            'type' => 'ticket_status_changed',
            'ticket_uuid' => $this->ticket->uuid,
            'ticket_title' => $this->ticket->title,
            'action' => $this->action,
            'actor_name' => $this->actor->name,
            'title' => "Ticket byl {$actionCs}",
            'body' => "„{$this->ticket->title}" . '"',
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

    private function actionUrl(): string
    {
        return "/tickets/{$this->ticket->uuid}";
    }

    private function shortUuid(): string
    {
        return substr((string) $this->ticket->uuid, 0, 8);
    }

    /**
     * Český popis akce pro subject + title.
     */
    private function actionCs(): string
    {
        return $this->action === self::ACTION_CLOSED ? 'uzavřen' : 'znovuotevřen';
    }

    /**
     * Je notifiable instancí host User modelu (z configu)?
     */
    private function isHostUser(object $notifiable): bool
    {
        $userModel = (string) config('tickets.models.user_model', 'App\\Models\\User');

        return class_exists($userModel) && $notifiable instanceof $userModel;
    }
}
