<?php

declare(strict_types=1);

/*
 * Web (Inertia) routy balíčku webyashopy/laravel-ticketing-system.
 *
 * Soubor načítá `TicketsServiceProvider` přes `->hasRoute('web')`
 * (Spatie Package Tools → `loadRoutesFrom`).
 *
 * Prefix / middleware / pojmenování (`as`) se čtou z `config('tickets.routes')`
 * — host aplikace si je upraví dle vlastní routovací struktury, aniž by
 * musela tento soubor přepisovat.
 *
 * Route names mají prefix `tickets.` (default `config('tickets.routes.as')`).
 */

use Illuminate\Support\Facades\Route;
use Webyashopy\Tickets\Http\Controllers\Api\TicketApiController;
use Webyashopy\Tickets\Http\Controllers\TicketAttachmentController;
use Webyashopy\Tickets\Http\Controllers\TicketCommentController;
use Webyashopy\Tickets\Http\Controllers\TicketController;

$config = (array) config('tickets.routes', []);

$prefix = (string) ($config['prefix'] ?? '');
$middleware = (array) ($config['middleware'] ?? ['web', 'auth']);
$as = (string) ($config['as'] ?? 'tickets.');

Route::middleware($middleware)
    ->prefix($prefix)
    ->name($as)
    ->group(function (): void {
        // === Tickety ===
        Route::prefix('tickets')->name('')->group(function (): void {
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::post('/', [TicketController::class, 'store'])->name('store');
            Route::get('/{ticket:uuid}', [TicketController::class, 'show'])->name('show');

            // Partial update title/description/category/priority.
            // Anti-IDOR: TicketPolicy::update (TicketAuthorizer) + route-model binding.
            Route::patch('/{ticket:uuid}', [TicketController::class, 'update'])->name('update');
            Route::post('/{ticket:uuid}/close', [TicketController::class, 'close'])->name('close');
            Route::post('/{ticket:uuid}/reopen', [TicketController::class, 'reopen'])->name('reopen');

            // Add/remove příloh existujícího ticketu.
            Route::post('/{ticket:uuid}/attachments', [
                TicketAttachmentController::class, 'store',
            ])->name('attachments.store');
            Route::delete('/{ticket:uuid}/attachments/{attachment:uuid}', [
                TicketAttachmentController::class, 'destroy',
            ])->name('attachments.destroy');

            // Signed-route stream přílohy (TTL z config('tickets.signed_url_ttl_hours')).
            // Validní podpis kontroluje 'signed' middleware, dále se v controlleru
            // ověří, že attachment opravdu patří k danému ticketu.
            Route::get('/{ticket:uuid}/attachments/{attachment:uuid}', [
                TicketApiController::class, 'attachment',
            ])->middleware('signed')->name('attachment.show');

            // Lineární komentáře pod ticketem.
            // Anti-IDOR: TicketCommentPolicy::create deleguje na TicketPolicy::view.
            Route::post('/{ticket:uuid}/comments', [
                TicketCommentController::class, 'store',
            ])->name('comments.store');
        });

        // Samostatné comment endpointy (mimo /tickets prefix) — UUID
        // identifikuje jednoznačně bez nutnosti ticket parametru.
        Route::patch('/comments/{comment:uuid}', [
            TicketCommentController::class, 'update',
        ])->name('comments.update');

        Route::delete('/comments/{comment:uuid}', [
            TicketCommentController::class, 'destroy',
        ])->name('comments.destroy');
    });
