<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webyashopy\Tickets\Database\Factories\TicketAuditLogFactory;

/**
 * Append-only audit log změn ticketu.
 *
 * Zachycuje editace, lifecycle eventy, attachment add/remove, comment events.
 * No-op změny (old == new) se nezapisují — handluje TicketAuditService.
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $user_id
 * @property string $field
 * @property string|null $old_value
 * @property string|null $new_value
 * @property \Illuminate\Support\Carbon $created_at
 */
class TicketAuditLog extends Model
{
    use HasFactory;

    // Append-only — žádné updated_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
    ];

    /**
     * Ticket, ke kterému audit záznam patří.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Uživatel, který změnu provedl.
     *
     * User model balíček čte z configu `tickets.models.user_model`
     *.
     */
    public function user(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('tickets.models.user_model', 'App\\Models\\User');

        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Factory balíčkového modelu (default resolver předpokládá app namespace).
     */
    protected static function newFactory(): Factory
    {
        return TicketAuditLogFactory::new();
    }
}
