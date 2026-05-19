<?php

declare(strict_types=1);

namespace Webyashopy\Tickets;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Webyashopy\Tickets\Console\Commands\TicketsStaleNotificationCommand;
use Webyashopy\Tickets\Console\Commands\TicketsSyncExportCommand;
use Webyashopy\Tickets\Contracts\TicketAuthorizer;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;
use Webyashopy\Tickets\Http\Middleware\ShareTicketsBadge;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketComment;
use Webyashopy\Tickets\Policies\TicketCommentPolicy;
use Webyashopy\Tickets\Policies\TicketPolicy;
use Webyashopy\Tickets\Support\NullTenantResolver;
use Webyashopy\Tickets\Support\OwnerTicketAuthorizer;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider balíčku webyashopy/laravel-ticketing-system.
 *
 * Odpovědnosti:
 *
 * Bindingy kontraktů multi-tenancy / autorizace na výchozí
 * single-tenant implementace — host aplikace je přepíše vlastním bindingem.
 *
 * Registrace migrací (modely + enumy v src/).
 *
 * HTTP vrstva — routy (`hasRoute`), Gate policy, middleware alias.
 *
 * Services + notifikace + mail + console commandy.
 *   - `hasViews('tickets')` načte email blade views pod namespace `tickets::`.
 *   - `hasCommands([...])` registruje `tickets:notify-stale` + `tickets:sync-export`.
 *   - scheduling commandů přes `callAfterResolving(Schedule::class, ...)`:
 *     `tickets:notify-stale` denně v 09:00; `tickets:sync-export` každých 5 min,
 *     ale JEN když je `config('tickets.sync_export_path')` vyplněn.
 *
 * `discoversMigrations()` automaticky najde všechny migrace v
 * `database/migrations`. `runsMigrations()` zajistí jejich spuštění přes
 * `loadMigrationsFrom`. `hasRoute('web' / 'api')` načte `routes/web.php` a
 * `routes/api.php` přes `loadRoutesFrom`.
 */
class TicketsServiceProvider extends PackageServiceProvider
{
    /**
     * Mapování modelů balíčku na jejich policy.
     *
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        Ticket::class => TicketPolicy::class,
        TicketComment::class => TicketCommentPolicy::class,
    ];

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ticketing-system')
            ->hasConfigFile('tickets')
            ->discoversMigrations()
            ->runsMigrations()
            ->hasRoute('web')
            ->hasRoute('api')
            // Email blade views pod namespace `tickets::` — notifikace
            // a mailable je adresují jako `tickets::emails.tickets.*`.
            ->hasViews('tickets')
            ->hasCommands([
                TicketsStaleNotificationCommand::class,
                TicketsSyncExportCommand::class,
            ]);
    }

    /**
     * Hook Spatie Package Tools — volá se uvnitř fáze `register()`.
     *
     * Bindujeme přes `bind()` (ne `singleton()`) — kontrakty jsou stateless,
     * ale `bind()` nechá host aplikaci snadno přepsat default vlastní třídou.
     */
    public function packageRegistered(): void
    {
        $this->app->bind(TicketTenantResolver::class, NullTenantResolver::class);
        $this->app->bind(TicketAuthorizer::class, OwnerTicketAuthorizer::class);
    }

    /**
     * Hook Spatie Package Tools — volá se uvnitř fáze `boot()`.
     *
     * Registruje Gate policy pro modely balíčku, zpřístupňuje middleware
     * `ShareTicketsBadge` pod aliasem `tickets.badge` (host aplikace si ho
     * zařadí do `web` skupiny) a plánuje console commandy do scheduleru.
     */
    public function packageBooted(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }

        $this->app['router']->aliasMiddleware('tickets.badge', ShareTicketsBadge::class);

        $this->scheduleCommands();
    }

    /**
     * Naplánuje console commandy balíčku do scheduleru host aplikace.
     *
     * `callAfterResolving(Schedule::class, ...)` — scheduler se v balíčku
     * nesmí resolvovat eagerly (boot pořadí), proto se plánování odloží až
     * na okamžik, kdy si host aplikace `Schedule` vyžádá.
     *
     *  - `tickets:notify-stale` — denně v 09:00, `withoutOverlapping`.
     *  - `tickets:sync-export` — každých 5 minut, ale JEN když je
     *    `config('tickets.sync_export_path')` vyplněn (jinak by se každých
     *    5 min zbytečně volal příkaz, který se sám přeskočí).
     */
    private function scheduleCommands(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            // Denní digest stale ticketů.
            $schedule->command('tickets:notify-stale')
                ->dailyAt('09:00')
                ->withoutOverlapping()
                ->name('tickets:notify-stale');

            // Markdown sync export — podmíněně dle configu.
            $syncPath = config('tickets.sync_export_path');

            if (is_string($syncPath) && trim($syncPath) !== '') {
                $schedule->command('tickets:sync-export')
                    ->everyFiveMinutes()
                    ->withoutOverlapping()
                    ->name('tickets:sync-export');
            }
        });
    }
}
