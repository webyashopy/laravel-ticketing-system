<?php

declare(strict_types=1);

use Webyashopy\Tickets\TicketsServiceProvider;

it('zaregistruje service provider balíčku', function () {
    expect(app()->getProviders(TicketsServiceProvider::class))->not->toBeEmpty();
});

it('načte konfiguraci tickets', function () {
    expect(config('tickets'))->toBeArray()
        ->and(config('tickets.models.user_model'))->toBe('App\Models\User');
});
