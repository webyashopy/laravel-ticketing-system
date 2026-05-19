<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webyashopy\Tickets\Contracts\TicketAuthorizer;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;
use Webyashopy\Tickets\Support\NullTenantResolver;
use Webyashopy\Tickets\Support\OwnerTicketAuthorizer;

it('nabinduje TicketTenantResolver na NullTenantResolver', function () {
    expect(app(TicketTenantResolver::class))->toBeInstanceOf(NullTenantResolver::class);
});

it('nabinduje TicketAuthorizer na OwnerTicketAuthorizer', function () {
    expect(app(TicketAuthorizer::class))->toBeInstanceOf(OwnerTicketAuthorizer::class);
});

it('NullTenantResolver je single-tenant pass-through', function () {
    $resolver = new NullTenantResolver();
    $user = (object) ['id' => 1];

    // scopeQuery vrací předaný Builder beze změny (žádné where(tenant_id))
    $query = makeDummyQuery();
    expect($resolver->scopeQuery($query, $user))->toBe($query);

    // tenantIdFor je null, canViewAllTenants false
    expect($resolver->tenantIdFor($user))->toBeNull()
        ->and($resolver->canViewAllTenants($user))->toBeFalse();
});

it('OwnerTicketAuthorizer povolí jen tvůrce ticketu', function () {
    $authorizer = new OwnerTicketAuthorizer();

    $owner = (object) ['id' => 7];
    $other = (object) ['id' => 99];
    $ticket = (object) ['user_id' => 7];

    expect($authorizer->canManage($owner, $ticket))->toBeTrue()
        ->and($authorizer->canDelete($owner, $ticket))->toBeTrue()
        ->and($authorizer->canManage($other, $ticket))->toBeFalse()
        ->and($authorizer->canDelete($other, $ticket))->toBeFalse();
});

it('OwnerTicketAuthorizer odmítne null vstupy', function () {
    $authorizer = new OwnerTicketAuthorizer();
    $ticket = (object) ['user_id' => 1];

    expect($authorizer->canManage(null, $ticket))->toBeFalse()
        ->and($authorizer->canDelete((object) ['id' => 1], null))->toBeFalse();
});

it('config obsahuje očekávané klíče', function () {
    expect(config('tickets.models.user_model'))->toBe('App\Models\User')
        ->and(config('tickets.features'))->toBeArray()
        ->and(config('tickets.features.notifications'))->toBeTrue()
        ->and(config('tickets.routes.as'))->toBe('tickets.')
        ->and(config('tickets.stale_email_to'))->toBeNull()
        ->and(config('tickets.allowed_attachment_mime_types'))->toContain('image/png')
        ->and(config('tickets.allowed_attachment_mime_types'))->not->toContain('image/svg+xml');
});

/**
 * Pomocník — vytvoří dummy Eloquent Builder.
 *
 * Model Ticket dodává host aplikace, proto si pro test sestavíme
 * Builder nad anonymním Eloquent modelem. NullTenantResolver Builder jen
 * propaguje beze změny, takže konkrétní model nehraje roli.
 */
function makeDummyQuery(): Builder
{
    $model = new class extends Model
    {
        protected $table = 'dummy';
    };

    return $model->newQuery();
}
