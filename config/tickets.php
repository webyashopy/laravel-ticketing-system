<?php

declare(strict_types=1);

/*
 * Konfigurace balíčku webyashopy/laravel-ticketing-system.
 *
 * Struktura dle config patternu (klíče `models` / `features` /
 * `routes`) + generalizace původního configu (stale digest, přílohy,
 * signed URL, sync export).
 *
 * POZOR: žádné closures — `config:cache` je neumí serializovat. Autorizace
 * a multi-tenancy proto jdou přes bindované kontrakty
 * (TicketTenantResolver / TicketAuthorizer), ne přes config closure.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Modely
    |--------------------------------------------------------------------------
    |
    | Modely, které host aplikace dodává nebo smí přepsat. Balíček si User
    | model nikdy neimportuje přímo — vždy ho čte přes config. Host User musí být Authenticatable + Notifiable
    | s atributy `name` / `email`.
    |
    */
    'models' => [
        'user_model' => env('TICKETS_USER_MODEL', 'App\Models\User'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Přepínače funkcí
    |--------------------------------------------------------------------------
    |
    | Toggle dílčích funkcí balíčku — host aplikace si nepotřebné vypne.
    |
    |  - sync_export:  scheduled export ticketů do git repa jako markdown
    |                  (skill `buguj`). Vyžaduje `sync_export_path`.
    |  - stale_digest: denní e-mailový digest dlouho otevřených ticketů.
    |  - notifications: in-app (zvonek) + mail notifikace o komentářích/změnách.
    |
    */
    'features' => [
        'sync_export' => (bool) env('TICKETS_FEATURE_SYNC_EXPORT', true),
        'stale_digest' => (bool) env('TICKETS_FEATURE_STALE_DIGEST', true),
        'notifications' => (bool) env('TICKETS_FEATURE_NOTIFICATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routy
    |--------------------------------------------------------------------------
    |
    | Prefix, middleware a pojmenování (`as`) rout balíčku. Host aplikace
    | upraví dle vlastní routovací struktury.
    |
    */
    'routes' => [
        'prefix' => env('TICKETS_ROUTE_PREFIX', ''),
        'middleware' => ['web', 'auth'],
        'as' => 'tickets.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stale digest (dlouho otevřené tickety)
    |--------------------------------------------------------------------------
    |
    |  - stale_threshold_days: po kolika dnech otevřený ticket spadne do
    |    denního digestu (idempotence přes `tickets.stale_email_sent_at`).
    |  - stale_email_to:       cílová adresa digestu (1 příjemce). Default
    |    `null` — host aplikace musí adresu explicitně nastavit, jinak se
    |    digest tiše přeskočí (balíček nemá vlastní výchozí adresu).
    |
    */
    'stale_threshold_days' => (int) env('TICKETS_STALE_DAYS', 3),

    'stale_email_to' => env('TICKETS_STALE_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Sync export (markdown → git repo)
    |--------------------------------------------------------------------------
    |
    | Cílová cesta pro příkaz `tickets:sync-export` — git repo, do kterého
    | se tickety exportují jako markdown pro lokální skill `buguj`. Bez
    | hodnoty (nebo neexistující cesty) se scheduled export tiše přeskočí.
    |
    */
    'sync_export_path' => env('TICKETS_SYNC_EXPORT_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Přílohy
    |--------------------------------------------------------------------------
    |
    |  - storage_disk:               filesystem disk pro přílohy.
    |  - max_attachments:            max počet souborů na jeden ticket.
    |  - max_attachment_size_bytes:  max velikost jedné přílohy v bytech.
    |  - signed_url_ttl_hours:       validita signed URL pro screenshot
    |                                (markdown export → Claude WebFetch).
    |
    */
    'storage_disk' => env('TICKETS_STORAGE_DISK', 'local'),

    'max_attachments' => (int) env('TICKETS_MAX_ATTACHMENTS', 20),

    'max_attachment_size_bytes' => (int) env('TICKETS_MAX_SIZE', 10 * 1024 * 1024),

    'signed_url_ttl_hours' => (int) env('TICKETS_SIGNED_TTL', 24),

    /*
    |--------------------------------------------------------------------------
    | Whitelist MIME typů příloh
    |--------------------------------------------------------------------------
    |
    | NIKDY nepřidávat:
    |   - 'image/svg+xml' (SVG umí JS = XSS vektor přes signed URL)
    |   - 'text/html' / 'application/xhtml+xml' (XSS vektor)
    |   - 'application/x-msdownload' / 'application/x-sh' / 'application/x-php'
    |     a podobné executable typy (RCE pokud by skončilo v public dir).
    |
    | `application/octet-stream` je povolen jen jako fallback pro extensions
    | txt/log/csv — viz `octet_stream_fallback_extensions` (defense in depth:
    | extension whitelist drží reálné restrikce).
    |
    */
    'allowed_attachment_mime_types' => [
        // Obrázky
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',

        // Dokumenty
        'application/pdf',

        // Plain-text formáty (logy, CSV)
        'text/plain',
        'text/csv',
        'application/csv',

        // Office (DOCX, XLSX)
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        // Archivy
        'application/zip',
        'application/x-zip-compressed',

        // Fallback pro .log/.txt/.csv kde PHP nedetekuje text MIME
        // (povoleno POUZE pokud extension je whitelist — viz storage service)
        'application/octet-stream',
    ],

    /*
    |--------------------------------------------------------------------------
    | Whitelist přípon příloh
    |--------------------------------------------------------------------------
    |
    | Pro validaci ve FormRequest (`mimes:...`). NIKDY nepřidávat
    | svg/html/htm/exe/bat/sh/php/js/vbs/jar/msi/dll/com/scr.
    |
    */
    'allowed_attachment_extensions' => [
        // Obrázky
        'jpg', 'jpeg', 'png', 'webp', 'gif',
        // Dokumenty
        'pdf',
        // Plain-text
        'txt', 'log', 'csv',
        // Office
        'docx', 'xlsx',
        // Archivy
        'zip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Octet-stream fallback přípony
    |--------------------------------------------------------------------------
    |
    | Přípony, které smí použít `application/octet-stream` MIME fallback.
    | Jiné MIME → strict whitelist.
    |
    */
    'octet_stream_fallback_extensions' => ['txt', 'log', 'csv'],

];
