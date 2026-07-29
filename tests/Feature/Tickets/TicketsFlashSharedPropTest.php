<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Feature\Tickets;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Webyashopy\Tickets\Http\Middleware\ShareTicketsBadge;

/**
 * Shared Inertia prop `ticketsFlash`.
 *
 * Na tomto propu stojí toast po vytvoření ticketu (`TicketController::store()`
 * flashne `tickets_created`, middleware to přepošle do Inertia props a
 * `TicketCreateModal` z toho složí hlášku s odkazem „Zobrazit"). Balíček
 * nespoléhá na `flash` prop host aplikace — ten nemá jak garantovat.
 *
 * Middleware voláme přímo (ne přes HTTP), aby test nezávisel na root view
 * host aplikace.
 */
class TicketsFlashSharedPropTest extends BaseTicketTest
{
    public function test_flash_klic_se_propise_do_inertia_propu(): void
    {
        $payload = [
            'id' => 42,
            'uuid' => 'b3f1c0de-0000-4000-8000-000000000000',
            'title' => 'Modal se nezavírá',
            'url' => 'http://localhost/tickets/b3f1c0de-0000-4000-8000-000000000000',
        ];

        $request = Request::create('/persons/123');
        $session = $this->app['session']->driver();
        $session->put('tickets_created', $payload);
        $request->setLaravelSession($session);

        $this->runMiddleware($request);

        $this->assertSame(['created' => $payload], Inertia::getShared('ticketsFlash'));
    }

    public function test_bez_flashe_je_created_null(): void
    {
        $request = Request::create('/persons/123');
        $request->setLaravelSession($this->app['session']->driver());

        $this->runMiddleware($request);

        $this->assertSame(['created' => null], Inertia::getShared('ticketsFlash'));
    }

    /**
     * Middleware může viset i na stateless skupině — bez session nesmí spadnout.
     */
    public function test_bez_session_middleware_neselze(): void
    {
        $request = Request::create('/persons/123');

        $this->runMiddleware($request);

        $this->assertSame(['created' => null], Inertia::getShared('ticketsFlash'));
    }

    private function runMiddleware(Request $request): void
    {
        (new ShareTicketsBadge())->handle($request, fn (): Response => new Response());
    }
}
