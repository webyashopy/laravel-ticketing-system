<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Controllers\Api;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Models\TicketAttachment;
use Webyashopy\Tickets\Services\TicketMarkdownExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * API endpointy pro tickety:
 *   - GET /api/tickets/{ticket:uuid}/export.md — markdown pro Claude Code
 *   - GET /tickets/{ticket:uuid}/attachments/{attachment:uuid} — signed-route
 *     stream přílohy
 */
class TicketApiController extends Controller
{
    public function __construct(
        private readonly TicketMarkdownExporter $exporter,
    ) {
    }

    /**
     * Markdown export ticketu pro Claude Code.
     *
     * Auth: session (auth middleware na route).
     * Anti-IDOR: Gate::authorize('view').
     *
     * Content-type: text/markdown; charset=utf-8 (browser ho nestáhne
     * jako soubor — odpověď lze číst přímo v DevTools / Claude WebFetch).
     */
    public function markdown(Ticket $ticket): Response
    {
        Gate::authorize('view', $ticket);

        $markdown = $this->exporter->export($ticket);

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    /**
     * Streamne přílohu (screenshot) ticketu.
     *
     * Auth: signed middleware (URL musí mít platný `signature`).
     * Plus extra check, že attachment opravdu patří k danému ticketu
     * (defense-in-depth proti URL manipulaci).
     */
    public function attachment(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        // Sanity check: attachment musí být součástí ticketu z URL.
        // Kdyby někdo zkombinoval signed URL z jiného ticketu, vyhodit 404.
        if ($attachment->ticket_id !== $ticket->id) {
            throw new NotFoundHttpException('Příloha nepatří k tomuto ticketu.');
        }

        $diskName = (string) config('tickets.storage_disk', 'local');
        $disk = Storage::disk($diskName);

        if (! $disk->exists($attachment->stored_path)) {
            throw new NotFoundHttpException('Soubor přílohy neexistuje.');
        }

        // Inline (zobrazení v <img>), ne attachment download.
        return $disk->response(
            $attachment->stored_path,
            $attachment->filename,
            ['Content-Type' => $attachment->mime_type],
        );
    }
}
