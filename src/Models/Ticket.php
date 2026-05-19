<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;
use Webyashopy\Tickets\Database\Factories\TicketFactory;
use Webyashopy\Tickets\Enums\TicketCategory;
use Webyashopy\Tickets\Enums\TicketPriority;
use Webyashopy\Tickets\Enums\TicketStatus;

/**
 * Interní bug-tracker / ticket.
 *
 * Visibility: per-tenant scope. Balíček sám tenant
 * model nezná — izolaci dat řeší bindovaný kontrakt {@see TicketTenantResolver}.
 * Single-tenant projekt nechává `tenant_id` NULL.
 *
 * Lifecycle: open → closed (audit přes `closed_at` + `closed_by_user_id`).
 *
 * @property int $id
 * @property string $uuid
 * @property int|string|null $tenant_id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property TicketCategory $category
 * @property TicketPriority $priority
 * @property TicketStatus $status
 * @property string|null $page_url
 * @property string|null $viewport
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property int|null $closed_by_user_id
 * @property \Illuminate\Support\Carbon|null $stale_email_sent_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'page_url',
        'viewport',
        'user_agent',
        'closed_at',
        'closed_by_user_id',
        'stale_email_sent_at',
    ];

    protected $casts = [
        'category' => TicketCategory::class,
        'priority' => TicketPriority::class,
        'status' => TicketStatus::class,
        'closed_at' => 'datetime',
        'stale_email_sent_at' => 'datetime',
    ];

    /**
     * Routovací klíč — UUID místo auto-incrementu (URL bezpečnost).
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
     * Tvůrce ticketu (creator).
     *
     * User model balíček nezná napřímo — čte ho z configu
     * `tickets.models.user_model`.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'user_id');
    }

    /**
     * Uživatel, který ticket zavřel (audit).
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'closed_by_user_id');
    }

    /**
     * Přílohy / screenshoty.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Append-only audit log změn.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(TicketAuditLog::class)->orderBy('created_at');
    }

    /**
     * Lineární komentáře.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at');
    }

    // === SCOPES ===

    /**
     * Tenant scope: omezí query na tickety viditelné pro daného uživatele.
     *
     * Balíček sám tenant model nezná — deleguje na bindovaný kontrakt
     * {@see TicketTenantResolver}. Single-tenant projekt (default
     * `NullTenantResolver`) vrací query beze změny; multi-tenant host
     * aplikace si nabinduje vlastní resolver (per-org scope apod.).
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace.
     * @param  bool  $allTenants  Cross-tenant pohled (např. `?all_orgs=1`).
     */
    public function scopeForUser(Builder $query, mixed $user, bool $allTenants = false): Builder
    {
        return app(TicketTenantResolver::class)->scopeQuery($query, $user, $allTenants);
    }

    /**
     * Pouze otevřené tickety.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::OPEN->value);
    }

    /**
     * Tickety, kde je daný uživatel tvůrcem (creator).
     */
    public function scopeCreatedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // === DOMAIN METHODS ===

    /**
     * Je ticket otevřený?
     */
    public function isOpen(): bool
    {
        return $this->status === TicketStatus::OPEN;
    }

    /**
     * Je ticket uzavřený?
     */
    public function isClosed(): bool
    {
        return $this->status === TicketStatus::CLOSED;
    }

    /**
     * Třída User modelu host aplikace (z configu).
     *
     * @return class-string<Model>
     */
    protected function userModel(): string
    {
        /** @var class-string<Model> $model */
        $model = config('tickets.models.user_model', 'App\\Models\\User');

        return $model;
    }

    /**
     * Factory balíčkového modelu.
     *
     * Default Laravel factory resolver předpokládá app namespace
     * (`Database\Factories\…`) — pro balíčkový model proto explicitně
     * určíme factory třídu pod namespace balíčku.
     */
    protected static function newFactory(): Factory
    {
        return TicketFactory::new();
    }
}
