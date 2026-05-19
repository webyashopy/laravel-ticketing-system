<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Webyashopy\Tickets\Http\Middleware\ShareTicketsBadge;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketComment;
use Webyashopy\Tickets\Policies\TicketCommentPolicy;
use Webyashopy\Tickets\Policies\TicketPolicy;

/*
 * Testy HTTP vrstvy balíčku — registrace rout, policy
 * a middlewaru přes TicketsServiceProvider.
 */

// Poslední test zakládá ticket → potřebujeme balíčkové migrace.
uses(RefreshDatabase::class);

it('zaregistruje pojmenované routy ticketů', function () {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->values()
        ->all();

    expect($names)
        ->toContain('tickets.index')
        ->toContain('tickets.store')
        ->toContain('tickets.show')
        ->toContain('tickets.update')
        ->toContain('tickets.close')
        ->toContain('tickets.reopen')
        ->toContain('tickets.attachments.store')
        ->toContain('tickets.attachments.destroy')
        ->toContain('tickets.attachment.show')
        ->toContain('tickets.comments.store')
        ->toContain('tickets.comments.update')
        ->toContain('tickets.comments.destroy')
        ->toContain('tickets.api.tickets.export-markdown');
});

it('routa pro stream přílohy má signed middleware', function () {
    $route = Route::getRoutes()->getByName('tickets.attachment.show');

    // `middleware()` vrací middleware deklarované přímo na routě/skupině
    // bez resolvu controlleru (controller závisí na službě balíčku).
    expect($route)->not->toBeNull()
        ->and($route->middleware())->toContain('signed');
});

it('zaregistruje policy pro Ticket a TicketComment', function () {
    expect(Gate::getPolicyFor(Ticket::class))->toBeInstanceOf(TicketPolicy::class)
        ->and(Gate::getPolicyFor(TicketComment::class))->toBeInstanceOf(TicketCommentPolicy::class);
});

it('zpřístupní middleware tickets.badge pod aliasem', function () {
    $aliases = app('router')->getMiddleware();

    expect($aliases)->toHaveKey('tickets.badge')
        ->and($aliases['tickets.badge'])->toBe(ShareTicketsBadge::class);
});

it('TicketPolicy deleguje update na bindovaný TicketAuthorizer', function () {
    // Default OwnerTicketAuthorizer = jen tvůrce ticketu smí spravovat.
    $ticket = Ticket::create([
        'tenant_id' => null,
        'user_id' => 1,
        'title' => 'Test',
        'description' => 'Popis',
        'category' => 'bug',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    $owner = (object) ['id' => 1];
    $other = (object) ['id' => 2];

    $policy = new TicketPolicy();

    expect($policy->update($owner, $ticket))->toBeTrue()
        ->and($policy->update($other, $ticket))->toBeFalse()
        ->and($policy->delete($owner, $ticket))->toBeTrue()
        ->and($policy->delete($other, $ticket))->toBeFalse();
});
