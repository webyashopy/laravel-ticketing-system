<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Webyashopy\Tickets\Console\Commands\TicketsStaleNotificationCommand;
use Webyashopy\Tickets\Console\Commands\TicketsSyncExportCommand;
use Webyashopy\Tickets\Services\TicketAttachmentStorage;
use Webyashopy\Tickets\Services\TicketAuditService;
use Webyashopy\Tickets\Services\TicketMarkdownExporter;
use Webyashopy\Tickets\Services\TicketNotificationDispatcher;
use Webyashopy\Tickets\Services\TicketStaleNotifier;

/*
 * Testy services + console commandů balíčku — ověřuje,
 * že service kontejner resolvuje všech 5 služeb a že console commandy
 * jsou registrované přes TicketsServiceProvider.
 */

it('service kontejner resolvuje všech 5 ticketových služeb', function () {
    expect(app(TicketAuditService::class))->toBeInstanceOf(TicketAuditService::class)
        ->and(app(TicketNotificationDispatcher::class))->toBeInstanceOf(TicketNotificationDispatcher::class)
        ->and(app(TicketAttachmentStorage::class))->toBeInstanceOf(TicketAttachmentStorage::class)
        ->and(app(TicketMarkdownExporter::class))->toBeInstanceOf(TicketMarkdownExporter::class)
        ->and(app(TicketStaleNotifier::class))->toBeInstanceOf(TicketStaleNotifier::class);
});

it('zaregistruje console commandy tickets:notify-stale a tickets:sync-export', function () {
    $commands = array_keys(app(\Illuminate\Contracts\Console\Kernel::class)->all());

    expect($commands)
        ->toContain('tickets:notify-stale')
        ->toContain('tickets:sync-export');
});

it('console commandy mají správné třídy', function () {
    $all = app(\Illuminate\Contracts\Console\Kernel::class)->all();

    expect($all['tickets:notify-stale'])->toBeInstanceOf(TicketsStaleNotificationCommand::class)
        ->and($all['tickets:sync-export'])->toBeInstanceOf(TicketsSyncExportCommand::class);
});

it('naplánuje tickets:notify-stale do scheduleru', function () {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $names = collect($schedule->events())
        ->map(fn ($event) => $event->description)
        ->filter()
        ->all();

    expect($names)->toContain('tickets:notify-stale');
});

it('NEnaplánuje tickets:sync-export bez nakonfigurované cesty', function () {
    // Default config: sync_export_path je null → sync-export se nesmí
    // naplánovat (jinak by se každých 5 min volal no-op příkaz).
    config()->set('tickets.sync_export_path', null);

    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $names = collect($schedule->events())
        ->map(fn ($event) => $event->description)
        ->filter()
        ->all();

    expect($names)->not->toContain('tickets:sync-export');
});

it('TicketAuditService má whitelist konstanty pro komentáře a přílohy', function () {
    expect(TicketAuditService::FIELD_COMMENT_ADDED)->toBe('comment_added')
        ->and(TicketAuditService::FIELD_COMMENT_DELETED)->toBe('comment_deleted')
        ->and(TicketAuditService::FIELD_ATTACHMENT_ADDED)->toBe('attachment_added')
        ->and(TicketAuditService::FIELD_ATTACHMENT_REMOVED)->toBe('attachment_removed');
});

it('tickets:sync-export se bez nakonfigurované cesty tiše přeskočí', function () {
    config()->set('tickets.sync_export_path', null);

    $this->artisan('tickets:sync-export')
        ->expectsOutputToContain('export přeskočen')
        ->assertSuccessful();
});
