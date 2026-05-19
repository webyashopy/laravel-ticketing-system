<?php

declare(strict_types=1);

/*
 * API routy balíčku webyashopy/laravel-ticketing-system.
 *
 * Soubor načítá `TicketsServiceProvider` přes `->hasRoute('api')`
 * (Spatie Package Tools → `loadRoutesFrom`).
 *
 * Jediný endpoint: markdown export ticketu pro Claude Code / skill `buguj`.
 * Auth + view scope řeší middleware skupiny a `Gate::authorize('view')`.
 *
 * Route name: prefix `config('tickets.routes.as')` + `api.tickets.export-markdown`
 * → defaultně `tickets.api.tickets.export-markdown`. Pro zachování původního
 * jména rout (`api.tickets.export-markdown`) si host aplikace nastaví
 * `config('tickets.routes.as')` na prázdný řetězec, nebo route přemapuje.
 */

use Illuminate\Support\Facades\Route;
use Webyashopy\Tickets\Http\Controllers\Api\TicketApiController;

$config = (array) config('tickets.routes', []);

$prefix = (string) ($config['prefix'] ?? '');
$middleware = (array) ($config['middleware'] ?? ['web', 'auth']);
$as = (string) ($config['as'] ?? 'tickets.');

Route::middleware($middleware)
    ->prefix($prefix)
    ->name($as)
    ->group(function (): void {
        // Markdown export ticketu pro Claude Code.
        Route::get('/api/tickets/{ticket:uuid}/export.md', [
            TicketApiController::class, 'markdown',
        ])->name('api.tickets.export-markdown');
    });
