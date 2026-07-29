<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Webyashopy\Tickets\Contracts\TicketTenantResolver;
use Webyashopy\Tickets\Enums\TicketCategory;
use Webyashopy\Tickets\Enums\TicketPriority;
use Webyashopy\Tickets\Enums\TicketStatus;
use Webyashopy\Tickets\Http\Requests\StoreTicketRequest;
use Webyashopy\Tickets\Http\Requests\UpdateTicketRequest;
use Webyashopy\Tickets\Models\Ticket;
use Webyashopy\Tickets\Services\TicketAttachmentStorage;
use Webyashopy\Tickets\Services\TicketAuditService;

/**
 * Web (Inertia) controller pro tickety.
 *
 * Anti-IDOR: list i detail jsou scope-ované per-tenant přes bindovaný
 * kontrakt {@see TicketTenantResolver} (single-tenant `NullTenantResolver`
 * = pass-through). Cross-tenant pohled (superadmin) řeší `canViewAllTenants()`
 * + parametr `?all_orgs=1`.
 *
 * Lifecycle: Open → Closed (přes `close` / `reopen`). Audit přes
 * `closed_at` + `closed_by_user_id`.
 *
 * POZN.: služby `TicketAttachmentStorage` / `TicketAuditService` /
 * `TicketNotificationDispatcher` dodává balíček (namespace
 * `Webyashopy\Tickets\Services\`). Reference přes `app()` / `class_exists()`
 * jsou tolerantní — controller je instanciovaný až za běhu rout.
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly TicketAttachmentStorage $attachmentStorage,
    ) {
    }

    /**
     * List ticketů s filtry. Render Inertia stránky `tickets/index`.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        Gate::authorize('viewAny', Ticket::class);

        $resolver = app(TicketTenantResolver::class);

        // Cross-tenant pohled: kdo na to má právo, vidí vše napříč tenanty
        // (i bez parametru). Ostatní jen na explicitní `?all_orgs=1`.
        $allTenants = $resolver->canViewAllTenants($user) || $request->boolean('all_orgs');

        // Filtry — všechny optional
        $statusFilter = $request->input('status');
        $categoryFilter = $request->input('category');
        $priorityFilter = $request->input('priority');
        $search = trim((string) $request->input('search', ''));

        $query = Ticket::query()
            ->forUser($user, $allTenants)
            ->with([
                'creator:id,name,email',
                'attachments:id,uuid,ticket_id,filename,mime_type,size_bytes',
            ])
            ->latest();

        if (is_string($statusFilter) && in_array($statusFilter, TicketStatus::values(), true)) {
            $query->where('status', $statusFilter);
        }

        if (is_string($categoryFilter) && in_array($categoryFilter, TicketCategory::values(), true)) {
            $query->where('category', $categoryFilter);
        }

        if (is_string($priorityFilter) && in_array($priorityFilter, TicketPriority::values(), true)) {
            $query->where('priority', $priorityFilter);
        }

        if ($search !== '') {
            $driver = $query->getQuery()->getConnection()->getDriverName();
            $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
            $needle = "%{$search}%";

            $query->where(function ($q) use ($likeOp, $needle): void {
                $q->where('title', $likeOp, $needle)
                    ->orWhere('description', $likeOp, $needle);
            });
        }

        $tickets = $query->paginate(25)->withQueryString();

        return Inertia::render('tickets/index', [
            'tickets' => $tickets,
            'filters' => [
                'status' => $statusFilter,
                'category' => $categoryFilter,
                'priority' => $priorityFilter,
                'search' => $search !== '' ? $search : null,
                'all_orgs' => $allTenants,
            ],
            'options' => [
                'statuses' => TicketStatus::options(),
                'categories' => TicketCategory::options(),
                'priorities' => TicketPriority::options(),
            ],
            'can' => [
                'viewAllOrgs' => $resolver->canViewAllTenants($user),
            ],
        ]);
    }

    /**
     * Detail ticketu. Render Inertia stránky `tickets/show`.
     *
     * Route binding {ticket:uuid}, anti-IDOR přes Gate::authorize('view').
     */
    public function show(Ticket $ticket): Response
    {
        Gate::authorize('view', $ticket);

        // Eager load comments + audit log pro detail UI
        $ticket->load([
            'creator:id,name,email',
            'closer:id,name,email',
            'attachments',
            'comments.author:id,name,email',
            'auditLogs.user:id,name,email',
        ]);

        $user = request()->user();

        // Připravíme přílohy s signed URL (přes accessor `signed_url`)
        $attachments = $ticket->attachments->map(fn ($a) => [
            'uuid' => $a->uuid,
            'filename' => $a->filename,
            'mime_type' => $a->mime_type,
            'size_bytes' => $a->size_bytes,
            'signed_url' => $a->signed_url,
        ])->values();

        // Komentáře s can_edit/can_delete flagy pro UI
        $comments = $ticket->comments->map(fn ($c) => [
            'uuid' => $c->uuid,
            'body' => $c->body,
            'body_html' => $c->body_html,
            'created_at' => $c->created_at?->toIso8601String(),
            'updated_at' => $c->updated_at?->toIso8601String(),
            'author' => $c->author
                ? [
                    'id' => $c->author->id,
                    'name' => $c->author->name,
                    'email' => $c->author->email,
                ]
                : null,
            'can_edit' => $user && $c->canBeEditedByUser($user),
            'can_delete' => $user && Gate::allows('delete', $c),
        ])->values();

        // Audit log events pro timeline
        $auditLog = $ticket->auditLogs->map(fn ($e) => [
            'field' => $e->field,
            'old_value' => $e->old_value,
            'new_value' => $e->new_value,
            'user' => $e->user
                ? [
                    'id' => $e->user->id,
                    'name' => $e->user->name,
                    'email' => $e->user->email,
                ]
                : null,
            'created_at' => $e->created_at?->toIso8601String(),
        ])->values();

        return Inertia::render('tickets/show', [
            'ticket' => [
                'uuid' => $ticket->uuid,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'category' => $ticket->category->value,
                'priority' => $ticket->priority->value,
                'status' => $ticket->status->value,
                'page_url' => $ticket->page_url,
                'viewport' => $ticket->viewport,
                'user_agent' => $ticket->user_agent,
                'closed_at' => $ticket->closed_at?->toIso8601String(),
                'created_at' => $ticket->created_at?->toIso8601String(),
                'updated_at' => $ticket->updated_at?->toIso8601String(),
                'creator' => $ticket->creator
                    ? [
                        'id' => $ticket->creator->id,
                        'name' => $ticket->creator->name,
                        'email' => $ticket->creator->email,
                    ]
                    : null,
                'closer' => $ticket->closer
                    ? [
                        'id' => $ticket->closer->id,
                        'name' => $ticket->closer->name,
                    ]
                    : null,
                'attachments' => $attachments,
                'comments' => $comments,
                'audit_log' => $auditLog,
                'can' => [
                    'update' => $user ? Gate::allows('update', $ticket) : false,
                    'delete' => $user ? Gate::allows('delete', $ticket) : false,
                ],
                'category_label' => $ticket->category->label(),
                'priority_label' => $ticket->priority->label(),
                'status_label' => $ticket->status->label(),
                'category_badge' => $ticket->category->daisyBadgeClass(),
                'priority_badge' => $ticket->priority->daisyBadgeClass(),
                'status_badge' => $ticket->status->daisyBadgeClass(),
            ],
            // Top-level can pro BC s existujícími views
            'can' => [
                'update' => $user ? Gate::allows('update', $ticket) : false,
                'delete' => $user ? Gate::allows('delete', $ticket) : false,
            ],
        ]);
    }

    /**
     * Vytvoří nový ticket + uploadne přílohy.
     *
     * Transakce: pokud kterákoli příloha selže → rollback ticketu i ostatních.
     * Soubory mimo DB transakci jsou už uložené (Storage), což je akceptovatelná
     * konzistence (orphan soubor jen ve worst case, cleanup může jet asynchronně).
     *
     * `tenant_id` se odvozuje z bindovaného `TicketTenantResolver::tenantIdFor()`
     * — single-tenant projekt nechává NULL, multi-tenant zapíše ID organizace.
     *
     * Návrat: `back()` (NE redirect na detail). Ticket se typicky hlásí přes
     * FAB z libovolné stránky host aplikace a přesměrování na detail by
     * uživatele vytrhlo z rozdělané práce. Místo toho zůstává na místě a
     * dostane toast s odkazem na nový ticket — data pro něj jdou flash session
     * klíčem `tickets_created`, který sdílí middleware `ShareTicketsBadge`
     * jako Inertia prop `ticketsFlash.created`. Klíč je záměrně plochý (ne
     * `tickets.created`): tečku by `Session::put()` rozbalilo na nested pole
     * pod klíčem `tickets` a mohlo přepsat session data host aplikace.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $user = $request->user();

        $tenantId = app(TicketTenantResolver::class)->tenantIdFor($user);

        $ticket = DB::transaction(function () use ($request, $user, $tenantId) {
            $ticket = Ticket::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'title' => $request->string('title')->toString(),
                'description' => $request->string('description')->toString(),
                'category' => $request->input('category'),
                'priority' => $request->input('priority'),
                'status' => TicketStatus::OPEN->value,
                'page_url' => $request->input('page_url'),
                'viewport' => $request->input('viewport'),
                'user_agent' => $request->input('user_agent'),
            ]);

            $files = $request->file('attachments', []);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file === null) {
                        continue;
                    }
                    $this->attachmentStorage->store($file, $ticket);
                }
            }

            return $ticket;
        });

        // Fallback na index řešíme pro případ requestu bez Referer hlavičky
        // (přímé volání endpointu, testy) — `back()` by jinak skončilo na '/'.
        return redirect()
            ->back(fallback: route('tickets.index'))
            ->with('success', sprintf('Ticket #%d byl vytvořen.', $ticket->id))
            ->with('tickets_created', [
                'id' => $ticket->id,
                'uuid' => $ticket->uuid,
                'title' => $ticket->title,
                'url' => route('tickets.show', ['ticket' => $ticket->uuid]),
            ]);
    }

    /**
     * Aktualizuje editovatelná pole ticketu.
     *
     * Partial update — UpdateTicketRequest používá `sometimes`, takže
     * `$validated` obsahuje pouze odeslaná pole (typicky 1 v inline editu).
     *
     * Side-effects v rámci jedné transakce:
     *   1) per-pole snapshot starých hodnot
     *   2) `$ticket->update($validated)`
     *   3) `TicketAuditService::recordMany()` — append-only audit log
     *   4) Pokud existuje TicketNotificationDispatcher → dispatchUpdated
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($ticket, $validated, $user): void {
            // Per-pole sledování změn pro audit log.
            // Enum se castuje na string (priority + category jsou BackedEnum).
            $changes = [];
            foreach (['title', 'description', 'category', 'priority'] as $field) {
                if (! array_key_exists($field, $validated)) {
                    continue;
                }

                $old = $ticket->{$field};
                $oldStr = $old instanceof \BackedEnum ? $old->value : (string) $old;
                $newStr = (string) $validated[$field];

                if ($oldStr !== $newStr) {
                    $changes[] = ['field' => $field, 'old' => $oldStr, 'new' => $newStr];
                }
            }

            $ticket->update($validated);

            if ($changes !== []) {
                app(TicketAuditService::class)->recordMany($ticket, $user, $changes);

                // Notifikace — soft dependency (feature toggle + class_exists).
                $dispatcher = \Webyashopy\Tickets\Services\TicketNotificationDispatcher::class;
                if (config('tickets.features.notifications', true) && class_exists($dispatcher)) {
                    app($dispatcher)->dispatchUpdated($ticket, $changes, $user);
                }
            }
        });

        return back()->with('success', 'Ticket aktualizován.');
    }

    /**
     * Zavře ticket — nastaví status, closed_at, closed_by_user_id.
     */
    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        if ($ticket->isClosed()) {
            return back()->with('info', 'Ticket je už zavřený.');
        }

        $ticket->update([
            'status' => TicketStatus::CLOSED->value,
            'closed_at' => now(),
            'closed_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Ticket byl zavřen.');
    }

    /**
     * Znovu otevře ticket — vynuluje closed_at a closed_by_user_id.
     */
    public function reopen(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        if ($ticket->isOpen()) {
            return back()->with('info', 'Ticket je už otevřený.');
        }

        $ticket->update([
            'status' => TicketStatus::OPEN->value,
            'closed_at' => null,
            'closed_by_user_id' => null,
            // Reset stale flagu — pokud byl už notifikován a je znovu open,
            // další stale notifikace proběhne za další threshold dnů.
            'stale_email_sent_at' => null,
        ]);

        return back()->with('success', 'Ticket byl znovu otevřen.');
    }
}
