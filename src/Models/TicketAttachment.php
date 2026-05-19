<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Database\Factories\TicketAttachmentFactory;

/**
 * Příloha / screenshot ticketu.
 *
 * Storage: per-ticket složka (NE SHA-256 dedup) v disku z configu
 * `tickets.storage_disk`:
 *   tickets/{ticket_uuid}/{attachment_uuid}.{ext}
 *
 * Přístup: signed URL přes route `tickets.attachment.show`,
 * default TTL 24 h (config('tickets.signed_url_ttl_hours')).
 *
 * @property int $id
 * @property string $uuid
 * @property int $ticket_id
 * @property string $filename
 * @property string $stored_path
 * @property string $mime_type
 * @property int $size_bytes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read string $signed_url
 */
class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'ticket_id',
        'filename',
        'stored_path',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    /**
     * Routovací klíč — UUID (signed URL pak nese uuid, ne id).
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Auto-generování UUID při create.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // === RELACE ===

    /**
     * Ticket, ke kterému příloha patří.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    // === ACCESSORY ===

    /**
     * Vrátí dočasné podepsané URL pro stažení / zobrazení obrázku.
     *
     * TTL bere z `config('tickets.signed_url_ttl_hours')`, default 24 h.
     * Použití: v markdown exportu (Claude WebFetch), v lightboxu detailu.
     */
    public function getSignedUrlAttribute(): string
    {
        $ttlHours = (int) config('tickets.signed_url_ttl_hours', 24);

        return URL::temporarySignedRoute(
            'tickets.attachment.show',
            now()->addHours($ttlHours),
            [
                'ticket' => $this->ticket->uuid,
                'attachment' => $this->uuid,
            ],
        );
    }

    /**
     * Factory balíčkového modelu (default resolver předpokládá app namespace).
     */
    protected static function newFactory(): Factory
    {
        return TicketAttachmentFactory::new();
    }
}
