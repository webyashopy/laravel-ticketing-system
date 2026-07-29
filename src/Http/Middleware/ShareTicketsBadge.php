<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;
use Webyashopy\Tickets\Models\Ticket;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sdílí Inertia prop `ticketsOpenCount` — počet otevřených ticketů
 * viditelných pro přihlášeného uživatele.
 *
 * Hodnota napájí badge u zvonku/odznáčku v hlavičce host aplikace.
 * Počet je scope-ovaný přes bindovaný kontrakt {@see TicketTenantResolver}
 * — single-tenant `NullTenantResolver` vrací globální počet, multi-tenant
 * host omezí na tickety v rámci tenanta.
 *
 * Lazy hodnota (closure) — Inertia ji vyhodnotí jen při partial reloadu,
 * který ji explicitně vyžádá, takže DB dotaz neběží na každém requestu.
 *
 * Host aplikace si middleware zařadí do `web` skupiny (nebo do skupiny
 * rout balíčku přes `config('tickets.routes.middleware')`).
 *
 * Vedle badge sdílí i prop `ticketsFlash` — flash data balíčku, na kterých
 * stojí toast po vytvoření ticketu (viz `TicketController::store()`).
 * Nespoléháme na `flash` prop host aplikace, protože ten balíček nemá
 * jak garantovat.
 */
class ShareTicketsBadge
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        Inertia::share('ticketsOpenCount', function () use ($user): int {
            // Bez přihlášeného uživatele nemá badge co počítat.
            if ($user === null) {
                return 0;
            }

            $resolver = app(TicketTenantResolver::class);

            // Otevřené tickety, scope-ované per-tenant přes kontrakt.
            $query = $resolver->scopeQuery(Ticket::query(), $user);

            return (int) $query->open()->count();
        });

        // Flash data balíčku — na rozdíl od badge NE lazy: flash session klíč
        // přežije jen jeden request, takže se musí vyhodnotit hned.
        // `hasSession()` — middleware může viset i na stateless skupině.
        Inertia::share('ticketsFlash', [
            'created' => $request->hasSession()
                ? $request->session()->get('tickets_created')
                : null,
        ]);

        return $next($request);
    }
}
