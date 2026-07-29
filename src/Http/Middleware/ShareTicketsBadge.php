<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Services\TicketCreatedFlash;
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
 * Vedle badge sdílí i prop `ticketsFlash` — payload pro toast po vytvoření
 * ticketu (viz `TicketController::store()` + {@see TicketCreatedFlash}).
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

        // Payload pro toast po vytvoření ticketu. Vyzvedáváme ho atomicky
        // (`pull` = přečti + smaž) a VÝHRADNĚ pro skutečný Inertia GET —
        // tedy pro ten jediný request, který má toast čím vyrenderovat.
        //
        // Bez toho guardu by payload spolykal kterýkoli paralelní request,
        // co zrovna proletí (React Query refetch, polling), a uživatel by po
        // založení ticketu nedostal žádnou odezvu. Přesně tomu se vyhýbáme
        // tím, že payload vůbec nedržíme v session flash — viz
        // {@see TicketCreatedFlash}.
        $isInertiaGet = $request->isMethod('GET') && $request->hasHeader('X-Inertia');

        Inertia::share('ticketsFlash', [
            'created' => ($user !== null && $isInertiaGet)
                ? app(TicketCreatedFlash::class)->pull($user->id)
                : null,
        ]);

        return $next($request);
    }
}
