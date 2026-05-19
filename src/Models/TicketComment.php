<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Webyashopy\Tickets\Database\Factories\TicketCommentFactory;

/**
 * Lineární komentář pod ticketem.
 *
 * Body je raw Markdown (max 5000 znaků). HTML render se generuje
 * v accessoru `body_html` přes `Str::markdown()` (league/commonmark) +
 * následnou sanitizaci přes allow-list HTML tagů.
 *
 * Edit window: autor smí editovat 5 minut po `created_at`. Po té policy
 * vrátí false (403). Delete: autor nebo superadmin.
 *
 * @property int $id
 * @property string $uuid
 * @property int $ticket_id
 * @property int|null $user_id
 * @property string $body
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read string $body_html
 * @property-read Ticket $ticket
 */
class TicketComment extends Model
{
    use HasFactory;

    /**
     * Edit window v minutách (proti retroaktivním úpravám historie).
     */
    public const EDIT_WINDOW_MINUTES = 5;

    /**
     * Allow-list HTML tagů, které projdou sanitizací v `body_html`.
     * Vše ostatní (`script`, `iframe`, `object`, `embed`, `style`,
     * inline `on*` event handlery) je odstraněno přes `strip_tags`
     * + regex pass přes atributy.
     */
    private const ALLOWED_HTML_TAGS = [
        'p', 'br', 'hr',
        'strong', 'em', 'b', 'i',
        'ul', 'ol', 'li',
        'code', 'pre',
        'blockquote',
        'a',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    ];

    protected $fillable = [
        'uuid',
        'ticket_id',
        'user_id',
        'body',
    ];

    /**
     * UUID jako routovací klíč (anti-IDOR).
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
     * Ticket, ke kterému komentář patří.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Autor komentáře (alias na user_id, ergonomičtější název v UI/JSON).
     *
     * User model balíček čte z configu `tickets.models.user_model`
     *.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), 'user_id');
    }

    // === ACCESSORS ===

    /**
     * HTML render komentáře — Markdown → safe HTML.
     *
     * Pipeline:
     *   1. `Str::markdown` (league/commonmark) — bezpečný markdown parser,
     *      sám neumožňuje raw HTML pokud není nastaven 'html_input' => 'allow'.
     *   2. `strip_tags` s allow-listem — odstraní script/iframe/style/svg.
     *   3. Regex pass — odstraní `on*` event-handler attributy a `javascript:`
     *      URL z `<a href>` (defenzivní vrstva, kdyby parser nepokryl).
     */
    public function bodyHtml(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $rendered = Str::markdown($this->body ?? '');

                // 1) Allow-list HTML tagů — strip_tags odstraní vše ostatní
                $allowed = '<' . implode('><', self::ALLOWED_HTML_TAGS) . '>';
                $sanitized = strip_tags($rendered, $allowed);

                // 2) Odstranění `on*` event handlerů ze všech tagů
                //    (např. `<a onclick="...">` → `<a>`)
                $sanitized = preg_replace(
                    '/\s*on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
                    '',
                    $sanitized,
                );

                // 3) Neutralizace `javascript:` URL v `href` (XSS prevence)
                $sanitized = preg_replace(
                    '/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i',
                    'href=$1#$1',
                    $sanitized,
                );

                return $sanitized ?? '';
            },
        );
    }

    // === DOMAIN METHODS ===

    /**
     * Smí daný user komentář editovat?
     * Pravidlo: autor && pořád běží 5-minutové edit okno.
     *
     * @param  mixed  $user  Autentizovaný uživatel host aplikace (atribut `id`).
     */
    public function canBeEditedByUser(mixed $user): bool
    {
        if ($user === null || $this->user_id !== ($user->id ?? null)) {
            return false;
        }

        return $this->created_at !== null
            && $this->created_at->gt(now()->subMinutes(self::EDIT_WINDOW_MINUTES));
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
     * Factory balíčkového modelu (default resolver předpokládá app namespace).
     */
    protected static function newFactory(): Factory
    {
        return TicketCommentFactory::new();
    }
}
