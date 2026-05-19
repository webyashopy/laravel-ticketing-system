<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Webyashopy\Tickets\Models\Ticket;

/**
 * Notifikace: editace ticketu — změna polí.
 *
 * $changedFields formát:
 *   [
 *     ['field' => 'priority', 'old' => 'low', 'new' => 'high'],
 *     ['field' => 'category', 'old' => 'bug',  'new' => 'feature'],
 *     ...
 *   ]
 *
 * Duck-typing $actor: balíček User model nezná napřímo, `$actor`
 * je typovaný volně (`object`), potřebné jen `->name`.
 */
class TicketUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Ticket  $ticket  Editovaný ticket
     * @param  array<int, array{field: string, old: mixed, new: mixed}>  $changedFields  Seznam změn
     * @param  object  $actor  Uživatel, který editaci provedl
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly array $changedFields,
        public readonly object $actor,
    ) {}

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
        $appName = (string) config('app.name', 'Tickets');

        return (new MailMessage())
            ->subject("[{$appName}] Ticket #{$uuidShort}: upraveno")
            ->markdown('tickets::emails.tickets.updated', [
                'ticket' => $this->ticket,
                'changedFields' => $this->changedFields,
                'actor' => $this->actor,
                'actionUrl' => $this->actionUrl(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $count = count($this->changedFields);

        return [
            'type' => 'ticket_updated',
            'ticket_uuid' => $this->ticket->uuid,
            'ticket_title' => $this->ticket->title,
            'actor_name' => $this->actor->name,
            'changed_fields' => $this->changedFields,
            'title' => 'Ticket upraven',
            'body' => $this->bodyText($count),
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
     * Český popis počtu změn (1 pole / 2-4 pole / 5+ polí).
     */
    private function bodyText(int $count): string
    {
        if ($count === 1) {
            return '1 pole změněno';
        }

        if ($count >= 2 && $count <= 4) {
            return "{$count} pole změněna";
        }

        return "{$count} polí změněno";
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
