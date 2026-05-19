<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Webyashopy\Tickets\Http\Requests\AddTicketAttachmentRequest;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAttachment;
use Webyashopy\Tickets\Services\TicketAttachmentStorage;
use Webyashopy\Tickets\Services\TicketAuditService;

/**
 * Add/remove příloh existujícího ticketu
 *.
 *
 * Endpointy:
 *   - POST   /tickets/{ticket:uuid}/attachments
 *   - DELETE /tickets/{ticket:uuid}/attachments/{attachment:uuid}
 *
 * Autorizace přes `TicketPolicy::update()` (deleguje na `TicketAuthorizer`).
 *
 * Per-ticket limit (`config('tickets.max_attachments')`, default 20) se
 * kontroluje v `store()` — pokud je už plný, vrátí back() s validation error.
 *
 * Audit:
 *   - add    → `FIELD_ATTACHMENT_ADDED`, new_value = filename
 *   - remove → `FIELD_ATTACHMENT_REMOVED`, old_value = filename
 */
class TicketAttachmentController extends Controller
{
    /**
     * Přidá přílohu k již existujícímu ticketu.
     *
     * Storage zápis + DB row + audit event je v jedné DB transakci.
     */
    public function store(AddTicketAttachmentRequest $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();

        // Per-ticket limit — kontrola PŘED upload (žádný soubor na disku,
        // pokud je už plno).
        $current = $ticket->attachments()->count();
        $max = (int) config('tickets.max_attachments', 20);
        if ($current >= $max) {
            return back()->withErrors(['file' => "Maximálně {$max} příloh na ticket."]);
        }

        DB::transaction(function () use ($ticket, $request, $user): void {
            $file = $request->file('file');
            $attachment = app(TicketAttachmentStorage::class)->store($file, $ticket);

            app(TicketAuditService::class)->record(
                $ticket,
                $user,
                TicketAuditService::FIELD_ATTACHMENT_ADDED,
                null,
                $attachment->filename,
            );
        });

        return back()->with('success', 'Příloha přidána.');
    }

    /**
     * Smaže přílohu z ticketu (hard delete: storage + DB row).
     *
     * Anti-IDOR sanity check: kontroluje `attachment.ticket_id === ticket.id`.
     * Router přiřadí oba modely podle UUID nezávisle, proto musíme ověřit,
     * že attachment skutečně patří do daného ticketu.
     */
    public function destroy(Ticket $ticket, TicketAttachment $attachment): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        // Anti-IDOR sanity check (cross-ticket UUID guessing)
        if ($attachment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $user = request()->user();

        DB::transaction(function () use ($ticket, $attachment, $user): void {
            // Filename si uložíme PŘED smazáním — po `delete()` model už nemá data.
            $filename = $attachment->filename;
            app(TicketAttachmentStorage::class)->delete($attachment);

            app(TicketAuditService::class)->record(
                $ticket,
                $user,
                TicketAuditService::FIELD_ATTACHMENT_REMOVED,
                $filename,
                null,
            );
        });

        return back()->with('success', 'Příloha smazána.');
    }
}
