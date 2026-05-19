<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Stubs;

use Webyashopy\Tickets\Contracts\TicketAuthorizer;

/**
 * Testovací role-based implementace {@see TicketAuthorizer}.
 *
 * Simuluje reálnou autorizační logiku host aplikace (v hostitelské aplikaci:
 * tvůrce ticketu NEBO superadmin smí spravovat / mazat libovolný ticket).
 * Slouží pro cross-org scénáře, které reálná aplikace testuje přes
 * `User::isSuperAdmin()`.
 *
 * Pravidla:
 *   - `canManage()` = uživatel je tvůrce ticketu, NEBO je superadmin.
 *   - `canDelete()` = stejné pravidlo (hard delete řeší tvůrce / superadmin).
 *
 * Balíček sám role nezná — tahle třída je čistě testovací stub bindovaný
 * v `defineEnvironment()` portovaných feature testů.
 */
final class MultiTenantTicketAuthorizer implements TicketAuthorizer
{
    /**
     * Smí uživatel ticket spravovat (editovat / zavřít / znovuotevřít)?
     *
     * Tvůrce ticketu vždy; superadmin napříč tenanty.
     */
    public function canManage(mixed $user, mixed $ticket): bool
    {
        if ($user === null || $ticket === null) {
            return false;
        }

        if ((bool) ($user->is_superadmin ?? false)) {
            return true;
        }

        return ($ticket->user_id ?? null) === ($user->id ?? null);
    }

    /**
     * Smí uživatel ticket smazat? Stejné pravidlo jako u `canManage()`.
     */
    public function canDelete(mixed $user, mixed $ticket): bool
    {
        return $this->canManage($user, $ticket);
    }
}
