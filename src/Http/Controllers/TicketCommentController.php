<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Webyashopy\Tickets\Http\Requests\StoreTicketCommentRequest;
use Webyashopy\Tickets\Http\Requests\UpdateTicketCommentRequest;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketComment;
use Webyashopy\Tickets\Services\TicketAuditService;
use Webyashopy\Tickets\Services\TicketNotificationDispatcher;

/**
 * Controller pro lineární komentáře pod ticketem
 *.
 *
 * Endpointy:
 *   - POST   /tickets/{ticket:uuid}/comments  → store
 *   - PATCH  /comments/{comment:uuid}         → update (5 min window)
 *   - DELETE /comments/{comment:uuid}         → destroy
 *
 * Anti-IDOR: vše přes Policy + route-model binding (cross-tenant → 404/403).
 */
class TicketCommentController extends Controller
{
    /**
     * Vytvoří komentář pod ticketem + audit event + notifikaci.
     */
    public function store(StoreTicketCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();

        // Transakce: insert komentáře + audit event v jedné atomic operaci
        $comment = DB::transaction(function () use ($ticket, $request, $user): TicketComment {
            $comment = $ticket->comments()->create([
                'user_id' => $user->id,
                'body' => $request->string('body')->toString(),
            ]);

            app(TicketAuditService::class)->record(
                $ticket,
                $user,
                TicketAuditService::FIELD_COMMENT_ADDED,
                null,
                $comment->uuid,
            );

            return $comment;
        });

        // Notifikace mimo transakci — chyba mailu nesmí zhroutit DB commit.
        // Soft dependency: feature toggle + class_exists.
        if (config('tickets.features.notifications', true)
            && class_exists(TicketNotificationDispatcher::class)
        ) {
            app(TicketNotificationDispatcher::class)
                ->dispatchCommentCreated($ticket, $comment, $user);
        }

        return back()->with('success', 'Komentář přidán.');
    }

    /**
     * Edit vlastního komentáře (do 5 minut po vytvoření).
     *
     * POZN: NEVYTVÁŘÍ audit event — chrání před retroaktivním spam logu
     * při typo opravách. Edit window v Policy zajistí, že user nemůže
     * přepsat staré komentáře.
     */
    public function update(UpdateTicketCommentRequest $request, TicketComment $comment): RedirectResponse
    {
        $comment->update([
            'body' => $request->string('body')->toString(),
        ]);

        return back()->with('success', 'Komentář upraven.');
    }

    /**
     * Hard delete komentáře (autor nebo správce ticketu) + audit event.
     */
    public function destroy(Request $request, TicketComment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $user = $request->user();

        DB::transaction(function () use ($comment, $user): void {
            // Old value pro audit (body se po delete ztratí)
            $oldBody = $comment->body;
            $ticket = $comment->ticket;

            $comment->delete();

            app(TicketAuditService::class)->record(
                $ticket,
                $user,
                TicketAuditService::FIELD_COMMENT_DELETED,
                $oldBody,
                null,
            );
        });

        return back()->with('success', 'Komentář smazán.');
    }
}
