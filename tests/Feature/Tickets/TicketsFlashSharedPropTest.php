<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Webyashopy\Tickets\Http\Middleware\ShareTicketsBadge;
use Webyashopy\Tickets\Services\TicketCreatedFlash;

/**
 * Shared Inertia prop `ticketsFlash`.
 *
 * Na tomto propu stojí toast po vytvoření ticketu (`TicketController::store()`
 * uloží payload do per-user cache, middleware ho atomicky vyzvedne a pošle do
 * Inertia props, `TicketCreateModal` z toho složí hlášku s odkazem).
 *
 * Klíčová vlastnost, kterou testy hlídají: payload smí spotřebovat VÝHRADNĚ
 * skutečný Inertia GET request. Jinak ho sní kterýkoli paralelní request, co
 * zrovna proletí (React Query refetch, polling), a uživatel po založení
 * ticketu nedostane žádnou odezvu — přesně ten race, kvůli kterému se od
 * session flash upustilo.
 *
 * Middleware voláme přímo (ne přes HTTP), aby test nezávisel na root view
 * host aplikace.
 */
class TicketsFlashSharedPropTest extends BaseTicketTest
{
    private const PAYLOAD = [
        'id' => 42,
        'uuid' => 'b3f1c0de-0000-4000-8000-000000000000',
        'title' => 'Modal se nezavírá',
        'url' => 'http://localhost/tickets/b3f1c0de-0000-4000-8000-000000000000',
    ];

    public function test_inertia_get_dostane_payload_do_propu(): void
    {
        $this->flashFor($this->user);

        $this->runMiddleware($this->inertiaGet());

        $this->assertSame(['created' => self::PAYLOAD], Inertia::getShared('ticketsFlash'));
    }

    public function test_payload_se_po_vyzvednuti_uz_neopakuje(): void
    {
        $this->flashFor($this->user);

        $this->runMiddleware($this->inertiaGet());
        $this->runMiddleware($this->inertiaGet());

        // Druhý průchod už nemá co zobrazit — jinak by toast naskočil znovu
        // při každé další Inertia navigaci.
        $this->assertSame(['created' => null], Inertia::getShared('ticketsFlash'));
    }

    /**
     * Jádro věci: non-Inertia request (typicky paralelní XHR na API) musí
     * payload nechat ležet, aby ho stihl vyzvednout Inertia GET.
     */
    public function test_paralelni_non_inertia_request_payload_nespotrebuje(): void
    {
        $this->flashFor($this->user);

        $apiRequest = Request::create('/api/dashboard/stats');
        $apiRequest->setUserResolver(fn () => $this->user);

        $this->runMiddleware($apiRequest);
        $this->assertSame(['created' => null], Inertia::getShared('ticketsFlash'));

        // Payload pořád čeká — Inertia GET ho dostane.
        $this->runMiddleware($this->inertiaGet());
        $this->assertSame(['created' => self::PAYLOAD], Inertia::getShared('ticketsFlash'));
    }

    /**
     * POST s hlavičkou X-Inertia (samotné odeslání formuláře) taky ne — toast
     * se vykresluje až na stránce, na kterou `back()` vrátí.
     */
    public function test_inertia_post_payload_nespotrebuje(): void
    {
        $this->flashFor($this->user);

        $post = Request::create('/tickets', 'POST');
        $post->headers->set('X-Inertia', 'true');
        $post->setUserResolver(fn () => $this->user);

        $this->runMiddleware($post);

        $this->assertSame(['created' => null], Inertia::getShared('ticketsFlash'));
        $this->assertNotNull(app(TicketCreatedFlash::class)->pull($this->user->id));
    }

    public function test_payload_je_per_user(): void
    {
        $this->flashFor($this->otherUser);

        $request = $this->inertiaGet();

        $this->runMiddleware($request);

        // Cizí payload se nesmí propsat — jinak by uživatel dostal odkaz na
        // ticket někoho jiného.
        $this->assertSame(['created' => null], Inertia::getShared('ticketsFlash'));
    }

    public function test_bez_prihlaseneho_usera_middleware_neselze(): void
    {
        $this->runMiddleware(Request::create('/persons/123'));

        $this->assertSame(['created' => null], Inertia::getShared('ticketsFlash'));
    }

    private function flashFor(mixed $user): void
    {
        app(TicketCreatedFlash::class)->put($user->id, self::PAYLOAD);
    }

    private function inertiaGet(): Request
    {
        $request = Request::create('/persons/123');
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn () => $this->user);

        return $request;
    }

    private function runMiddleware(Request $request): void
    {
        (new ShareTicketsBadge())->handle($request, fn (): Response => new Response());
    }
}
