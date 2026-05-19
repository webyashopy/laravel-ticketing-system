<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Schema;
use Webyashopy\Tickets\Contracts\TicketAuthorizer;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;
use Webyashopy\Tickets\Http\Middleware\ShareTicketsBadge;
use Webyashopy\Tickets\Tests\Stubs\MultiTenantTicketAuthorizer;
use Webyashopy\Tickets\Tests\Stubs\MultiTenantTicketResolver;
use Webyashopy\Tickets\Tests\Stubs\User;
use Webyashopy\Tickets\Tests\TestCase;

/**
 * Sdílený setup pro portované feature testy ticketing modulu.
 *
 * Port z původní implementace `tests/Feature/Tickets/BaseTicketTest`.
 * Klíčové rozdíly oproti původnímu originálu:
 *
 *   - Tenant `Organization` → generický sloupec `tickets.tenant_id` /
 *     `users.tenant_id`. Izolaci řeší bindovaný {@see MultiTenantTicketResolver}.
 *   - `User::isSuperAdmin()` (Role model host aplikace) → bool sloupec
 *     `users.is_superadmin` na testovacím stub Useru. Cross-tenant chování
 *     řídí testovací implementace kontraktů, ne host-specifická role.
 *
 * Připraví:
 *   - 2 „tenanty" (číselné ID `1` a `2`) — kvůli anti-IDOR scénářům,
 *   - 1 běžného usera (`user`) v tenantu 1,
 *   - 1 cizího usera (`otherUser`) v tenantu 2,
 *   - helper `superAdmin()` pro cross-tenant scénáře.
 *
 * Testovací prostředí binduje multi-tenant implementace kontraktů — tím
 * portované testy reprodukují reálné per-org chování bez vazby na konkrétní aplikaci.
 */
abstract class BaseTicketTest extends TestCase
{
    use RefreshDatabase;

    /** ID prvního tenanta (analogie `organization`). */
    protected int $tenantId = 1;

    /** ID druhého tenanta (analogie `otherOrganization`). */
    protected int $otherTenantId = 2;

    protected User $user;

    protected User $otherUser;

    /**
     * Testovací prostředí — in-memory SQLite + multi-tenant kontrakty.
     *
     * Stub User model + bindingy {@see MultiTenantTicketResolver} /
     * {@see MultiTenantTicketAuthorizer} simulují reálnou host aplikaci.
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Feature testy dělají HTTP POST/PATCH → session + CSRF potřebují
        // application key (jinak MissingAppKeyException).
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // SQLite má FK constrainty defaultně vypnuté — zapneme je, aby
        // `onDelete('cascade')` z balíčkových migrací reálně mazalo
        // navázané komentáře / audit logy / přílohy (cascade-delete testy).
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);

        // Inertia v Testbench renderuje root view `app` — host appka ho
        // dodává, my registrujeme minimální stub z `tests/stubs/views`.
        $app['config']->set('view.paths', array_merge(
            (array) $app['config']->get('view.paths', []),
            [__DIR__ . '/../../stubs/views'],
        ));

        // Balíček nedodává FE page komponenty (TSX) — ty řeší host aplikace.
        // Vypneme proto kontrolu fyzické existence page
        // souboru, `assertInertia()->component(...)` jen ověří jméno v props.
        $app['config']->set('inertia.testing.ensure_pages_exist', false);

        // Middleware `tickets.badge` (sdílí prop `ticketsOpenCount`) zařadíme
        // přímo do skupiny rout balíčku přes config — host appka to dělá
        // stejně. Spolehlivější než `pushMiddlewareToGroup` po bootu routeru.
        $app['config']->set('tickets.routes.middleware', ['web', 'auth', ShareTicketsBadge::class]);

        // Balíček čte User model z configu — namíříme ho na testovací stub.
        $app['config']->set('tickets.models.user_model', User::class);

        // App name kvůli prefixu předmětu notifikačních e-mailů (`[Tickets] ...`).
        $app['config']->set('app.name', 'Tickets');

        // Multi-tenant implementace kontraktů — místo single-tenant defaultů.
        $app->bind(TicketTenantResolver::class, MultiTenantTicketResolver::class);
        $app->bind(TicketAuthorizer::class, MultiTenantTicketAuthorizer::class);

        // Host aplikace si `ShareTicketsBadge` zařazuje do `web` skupiny —
        // v Testbench to musíme udělat ručně, jinak se prop `ticketsOpenCount`
        // nesdílí. Balíček middleware registruje pod aliasem `tickets.badge`.
        $app['router']->pushMiddlewareToGroup('web', ShareTicketsBadge::class);
    }

    /**
     * Migrace testovací DB.
     *
     * Stub tabulku `users` rozšiřujeme o `tenant_id` + `is_superadmin`
     * (testovací User a multi-tenant resolver je potřebují). Musí vzniknout
     * PŘED balíčkovými migracemi `tickets`/`ticket_*` (FK na `users`).
     */
    protected function defineDatabaseMigrations(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->boolean('is_superadmin')->default(false);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Standardní Laravel `notifications` tabulka — host appka ji dodává,
        // balíček ji nemigruje. `database` kanál notifikací (in-app zvonek)
        // do ní zapisuje, takže ve feature testech bez `Notification::fake()`
        // musí existovat.
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Routy testovací aplikace.
     *
     * `auth` middleware přesměrovává neautentizované requesty na
     * pojmenovanou routu `login` — host aplikace ji má, Testbench ne.
     * Definujeme proto minimální stub, aby anti-auth scénáře nevyhodily
     * `RouteNotFoundException` místo očekávaného redirectu.
     */
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->get('/login', fn () => 'login stub')->name('login');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenantId,
        ]);

        $this->otherUser = User::factory()->create([
            'tenant_id' => $this->otherTenantId,
        ]);
    }

    /**
     * Vytvoří superadmina (cross-tenant viditelnost + správa).
     *
     * @param  int|null  $tenantId  Domovský tenant superadmina (default 1).
     */
    protected function superAdmin(?int $tenantId = null): User
    {
        return User::factory()->superAdmin()->create([
            'tenant_id' => $tenantId ?? $this->tenantId,
        ]);
    }
}
