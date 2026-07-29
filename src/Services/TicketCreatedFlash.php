<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Jednorázový payload o právě vytvořeném ticketu — podklad pro toast
 * „Ticket #… byl vytvořen" s odkazem na detail.
 *
 * PROČ CACHE A NE SESSION FLASH:
 *
 * Session flash (ani `Inertia::flash()`) tuhle úlohu spolehlivě nezvládne.
 * Po `POST /tickets` vrací controller `back()`, takže klient pošle Inertia GET
 * na referer — jenže současně s ním letí i další requesty aplikace (React
 * Query refetche, polling, …). Session driver `database` nemá mezi requesty
 * zamykání, takže flash data spotřebuje kterýkoli z nich, klidně dřív než
 * dorazí ten Inertia GET, který jediný umí toast vyrenderovat. Výsledek:
 * ticket se vytvoří, ale uživatel nedostane žádnou zpětnou vazbu — a chyba
 * je náhodná podle časování, tedy mizerně reprodukovatelná.
 *
 * Řešení: payload leží v Cache pod klíčem odvozeným od ID uživatele a čte se
 * atomickým `pull()` (přečti + smaž) teprve v okamžiku, kdy ho má kdo zobrazit
 * — viz {@see \Webyashopy\Tickets\Http\Middleware\ShareTicketsBadge}, který
 * čte jen pro skutečný Inertia GET request.
 *
 * Objevy vděčí T4A (TASK-1258a-fix-2), kde na tenhle race narazili v ostrém
 * provozu; balíček přebírá jejich řešení, aby ho každý host nemusel obcházet
 * vlastním overridem controlleru.
 *
 * TTL je krátká záměrně — payload má přežít jen redirect, ne uživatelovu
 * relaci. Když ho nemá kdo vyzvednout (host nepoužívá `ShareTicketsBadge`),
 * po vypršení zmizí sám.
 */
final class TicketCreatedFlash
{
    /**
     * Uloží payload pro daného uživatele.
     *
     * @param  array{id: int, uuid: string, title: string, url: string}  $payload
     */
    public function put(int|string $userId, array $payload): void
    {
        Cache::put($this->key($userId), $payload, $this->ttlSeconds());
    }

    /**
     * Atomicky vyzvedne a zahodí payload. Vrací null, pokud žádný nečeká.
     *
     * @return array{id: int, uuid: string, title: string, url: string}|null
     */
    public function pull(int|string $userId): ?array
    {
        /** @var array{id: int, uuid: string, title: string, url: string}|null $payload */
        $payload = Cache::pull($this->key($userId));

        return $payload;
    }

    /**
     * Klíč je namespaceovaný prefixem balíčku, aby nekolidoval s klíči hostu.
     */
    private function key(int|string $userId): string
    {
        return "tickets:created:{$userId}";
    }

    private function ttlSeconds(): int
    {
        return (int) config('tickets.created_flash_ttl_seconds', 30);
    }
}
