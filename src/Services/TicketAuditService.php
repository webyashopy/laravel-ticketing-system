<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Services;

use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAuditLog;

/**
 * Append-only audit log služba pro tickety.
 *
 * Použití:
 *   - `record()` zapíše jednotlivý event (no-op pokud old == new)
 *   - `recordMany()` bulk zápis pro edit endpoint (více polí v transakci)
 *
 * Whitelist polí přes konstanty FIELD_* — chrání před překlepem v controlleru.
 *
 * POZN.: `$user` je typovaný volně (`?object`) — balíček User model nezná
 * napřímo, čte ho host aplikace z `config('tickets.models.user_model')`.
 * Service potřebuje jen `->id` (duck-typed).
 */
final class TicketAuditService
{
    // Standardní fields ticketu (editovatelné)
    public const FIELD_TITLE = 'title';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_CATEGORY = 'category';
    public const FIELD_PRIORITY = 'priority';
    public const FIELD_STATUS = 'status';

    // Lifecycle eventy nad attachmenty
    public const FIELD_ATTACHMENT_ADDED = 'attachment_added';
    public const FIELD_ATTACHMENT_REMOVED = 'attachment_removed';

    // Lifecycle eventy nad komentáři
    public const FIELD_COMMENT_ADDED = 'comment_added';
    public const FIELD_COMMENT_DELETED = 'comment_deleted';

    public const ALLOWED_FIELDS = [
        self::FIELD_TITLE,
        self::FIELD_DESCRIPTION,
        self::FIELD_CATEGORY,
        self::FIELD_PRIORITY,
        self::FIELD_STATUS,
        self::FIELD_ATTACHMENT_ADDED,
        self::FIELD_ATTACHMENT_REMOVED,
        self::FIELD_COMMENT_ADDED,
        self::FIELD_COMMENT_DELETED,
    ];

    /**
     * Zapíše jednotlivý audit event. Vrací null pokud no-op (old == new).
     *
     * @param  object|null  $user  User instance host aplikace (duck-typed, ->id).
     */
    public function record(
        Ticket $ticket,
        ?object $user,
        string $field,
        ?string $oldValue,
        ?string $newValue,
    ): ?TicketAuditLog {
        if (! in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Pole "%s" není ve whitelistu auditních polí.',
                $field,
            ));
        }

        // No-op — žádný zápis pro stejnou hodnotu
        if ($oldValue === $newValue) {
            return null;
        }

        return TicketAuditLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    /**
     * Bulk zápis více polí najednou. Volaný z edit endpointu.
     *
     * @param  object|null  $user  User instance host aplikace (duck-typed, ->id).
     * @param  array<int, array{field: string, old: ?string, new: ?string}>  $changes
     * @return array<int, TicketAuditLog> Zapsané eventy (bez no-opů)
     */
    public function recordMany(Ticket $ticket, ?object $user, array $changes): array
    {
        $written = [];

        foreach ($changes as $change) {
            $event = $this->record(
                $ticket,
                $user,
                $change['field'],
                $change['old'] ?? null,
                $change['new'] ?? null,
            );

            if ($event !== null) {
                $written[] = $event;
            }
        }

        return $written;
    }
}
