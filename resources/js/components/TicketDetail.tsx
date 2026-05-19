// Detail ticketu (content stránky TicketDetailPage).

import { router } from '@inertiajs/react';
import { Check, Clipboard, ExternalLink, Lock, Pencil, Trash2, Unlock, Upload, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

import { csrfHeaders } from '../lib/api';
import { formatDateTime } from '../lib/format-date';
import type { Ticket, TicketAttachment, TicketCategory, TicketPriority } from '../types';
import {
    TICKET_CATEGORY_BADGE_CLASS,
    TICKET_CATEGORY_LABELS,
    TICKET_PRIORITY_BADGE_CLASS,
    TICKET_PRIORITY_LABELS,
    TICKET_STATUS_BADGE_CLASS,
    TICKET_STATUS_LABELS,
} from '../types';

import { getAttachmentIcon, isImageMime } from './_helpers/attachmentIcon';
import { TicketAuditTimeline } from './TicketAuditTimeline';
import { TicketCommentComposer } from './TicketCommentComposer';
import { TicketComments } from './TicketComments';
import { TicketScreenshotLightbox } from './TicketScreenshotLightbox';

interface TicketDetailProps {
    ticket: Ticket;
}

// Interval pollingu detailu ticketu — „téměř live" chat bez WebSocket infry.
// Analogie NOTIFICATIONS_POLL_MS v app-header.tsx, jen kratší (komentáře = svižnější UX).
const TICKET_POLL_MS = 12_000;

// Sjednoceno přes balíčkový lib/format-date.
const formatDate = (iso: string | null) => formatDateTime(iso);

// Naformátuje velikost souboru (KB / MB) pro UI příloh
function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
}

export function TicketDetail({ ticket }: TicketDetailProps) {
    const [lightbox, setLightbox] = useState<TicketAttachment | null>(null);
    const [busy, setBusy] = useState(false);

    // edit mode pro title/description/category/priority
    const canUpdate = ticket.can?.update ?? false;
    const [editing, setEditing] = useState(false);
    const [editTitle, setEditTitle] = useState(ticket.title);
    const [editDescription, setEditDescription] = useState(ticket.description);
    const [editCategory, setEditCategory] = useState<TicketCategory>(ticket.category);
    const [editPriority, setEditPriority] = useState<TicketPriority>(ticket.priority);

    // attachment upload (přidání další přílohy)
    const fileInputRef = useRef<HTMLInputElement>(null);

    // „téměř live" polling detailu ticketu.
    // Reloaduje jen Inertia prop `ticket` (partial reload) à TICKET_POLL_MS,
    // aby nové komentáře přibývaly bez ručního refreshe.
    // router.reload() defaultně zachovává scroll i lokální stav stránky →
    // rozepsaný text v composeru, rozeditovaný komentář i otevřený lightbox
    // reload přežijí (analogicky reloadNotifications v app-header.tsx).
    useEffect(() => {
        let timer: number | undefined;

        // Partial reload jen propu `ticket` (komentáře jsou pod ticket.comments).
        const reloadTicket = () => {
            router.reload({
                only: ['ticket'],
            });
        };

        const startPolling = () => {
            if (timer !== undefined) return;
            timer = window.setInterval(reloadTicket, TICKET_POLL_MS);
        };

        const stopPolling = () => {
            if (timer === undefined) return;
            window.clearInterval(timer);
            timer = undefined;
        };

        // Skrytá záložka → polling pozastavíme (šetříme requesty).
        // Návrat na záložku → ihned 1× reload (dožene zameškané) + obnovení intervalu.
        const handleVisibilityChange = () => {
            if (document.hidden) {
                stopPolling();
            } else {
                reloadTicket();
                startPolling();
            }
        };

        // Při mountu spustíme polling jen pokud je záložka viditelná.
        if (!document.hidden) {
            startPolling();
        }
        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            stopPolling();
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
        // router je singleton (stabilní reference) → prázdné deps, polling běží po celou
        // dobu života komponenty.
    }, []);

    const handleSaveEdit = () => {
        setBusy(true);
        router.patch(
            `/tickets/${ticket.uuid}`,
            {
                title: editTitle,
                description: editDescription,
                category: editCategory,
                priority: editPriority,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditing(false);
                    toast.success('Ticket upraven');
                },
                onError: () => toast.error('Úprava selhala'),
                onFinish: () => setBusy(false),
            },
        );
    };

    const handleCancelEdit = () => {
        setEditTitle(ticket.title);
        setEditDescription(ticket.description);
        setEditCategory(ticket.category);
        setEditPriority(ticket.priority);
        setEditing(false);
    };

    const handleAttachmentUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setBusy(true);
        router.post(
            `/tickets/${ticket.uuid}/attachments`,
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => toast.success('Příloha přidána'),
                onError: (errors) => {
                    const msg = errors.file ?? 'Nahrání selhalo';
                    toast.error(typeof msg === 'string' ? msg : 'Chyba');
                },
                onFinish: () => {
                    setBusy(false);
                    if (fileInputRef.current) fileInputRef.current.value = '';
                },
            },
        );
    };

    const handleAttachmentDelete = (att: TicketAttachment) => {
        if (!confirm(`Smazat přílohu „${att.filename}"?`)) return;
        setBusy(true);
        router.delete(`/tickets/${ticket.uuid}/attachments/${att.uuid}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Příloha smazána'),
            onError: () => toast.error('Smazání selhalo'),
            onFinish: () => setBusy(false),
        });
    };

    const handleToggleStatus = () => {
        const action = ticket.status === 'open' ? 'close' : 'reopen';
        setBusy(true);
        router.post(
            `/tickets/${ticket.uuid}/${action}`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setBusy(false),
                onSuccess: () => {
                    toast.success(action === 'close' ? 'Ticket byl zavřen' : 'Ticket byl znovu otevřen');
                },
                onError: () => toast.error('Akce selhala'),
            },
        );
    };

    const handleCopyClaudePrompt = async () => {
        try {
            // Voláme přímo fetch (markdown není JSON) — manuálně doplníme CSRF headers.
            const response = await fetch(`/api/tickets/${ticket.uuid}/export.md`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    Accept: 'text/markdown',
                    ...csrfHeaders(),
                },
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const markdown = await response.text();
            const prompt = `/validate\n\n${markdown}\n\nZvaliduj tento bug — analyzuj příčinu a připrav podklad pro opravu.`;
            await navigator.clipboard.writeText(prompt);
            toast.success('Zkopírováno do schránky');
        } catch (error) {
            const msg = error instanceof Error ? error.message : 'Neznámá chyba';
            toast.error(`Nepodařilo se zkopírovat: ${msg}`);
        }
    };

    const handleOpenMarkdown = () => {
        window.open(`/api/tickets/${ticket.uuid}/export.md`, '_blank');
    };

    return (
        <div className="space-y-4">
            {/* Hlavička s tlačítky */}
            <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div className="flex-1">
                    <div className="flex flex-wrap items-center gap-2 mb-2">
                        <span className={`badge ${TICKET_STATUS_BADGE_CLASS[ticket.status]}`}>
                            {TICKET_STATUS_LABELS[ticket.status]}
                        </span>
                        <span className={`badge ${TICKET_CATEGORY_BADGE_CLASS[ticket.category]}`}>
                            {TICKET_CATEGORY_LABELS[ticket.category]}
                        </span>
                        <span className={`badge ${TICKET_PRIORITY_BADGE_CLASS[ticket.priority]}`}>
                            {TICKET_PRIORITY_LABELS[ticket.priority]}
                        </span>
                    </div>
                    <h1 className="text-2xl font-bold">{ticket.title}</h1>
                </div>

                <div className="flex flex-wrap gap-2">
                    {/* edit tlačítko (tvůrce nebo superadmin) */}
                    {canUpdate && !editing && (
                        <button
                            type="button"
                            className="btn btn-sm btn-outline btn-primary"
                            onClick={() => setEditing(true)}
                        >
                            <Pencil size={16} />
                            Upravit
                        </button>
                    )}
                    <button
                        type="button"
                        className="btn btn-sm btn-ghost"
                        onClick={handleCopyClaudePrompt}
                    >
                        <Clipboard size={16} />
                        Kopírovat jako Claude prompt
                    </button>
                    <button
                        type="button"
                        className="btn btn-sm btn-ghost"
                        onClick={handleOpenMarkdown}
                    >
                        <ExternalLink size={16} />
                        Otevřít markdown
                    </button>
                    <button
                        type="button"
                        className={`btn btn-sm ${ticket.status === 'open' ? 'btn-error' : 'btn-success'}`}
                        onClick={handleToggleStatus}
                        disabled={busy}
                    >
                        {ticket.status === 'open' ? <Lock size={16} /> : <Unlock size={16} />}
                        {ticket.status === 'open' ? 'Zavřít' : 'Znovu otevřít'}
                    </button>
                </div>
            </div>

            {/* Edit mode formulář (inline) */}
            {editing && (
                <div className="card bg-base-100 border-2 border-warning shadow-sm">
                    <div className="card-body p-4">
                        <h2 className="card-title text-lg">Úprava ticketu</h2>
                        <div className="flex flex-col gap-1">
                            <label className="label py-1">
                                <span className="text-sm font-medium">Název</span>
                            </label>
                            <input
                                type="text"
                                className="input input-bordered w-full"
                                value={editTitle}
                                onChange={(e) => setEditTitle(e.target.value)}
                                maxLength={255}
                                disabled={busy}
                            />
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                            <div className="flex flex-col gap-1">
                                <label className="label py-1">
                                    <span className="text-sm font-medium">Kategorie</span>
                                </label>
                                <select
                                    className="select select-bordered w-full"
                                    value={editCategory}
                                    onChange={(e) => setEditCategory(e.target.value as TicketCategory)}
                                    disabled={busy}
                                >
                                    {(Object.keys(TICKET_CATEGORY_LABELS) as TicketCategory[]).map((c) => (
                                        <option key={c} value={c}>
                                            {TICKET_CATEGORY_LABELS[c]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex flex-col gap-1">
                                <label className="label py-1">
                                    <span className="text-sm font-medium">Priorita</span>
                                </label>
                                <select
                                    className="select select-bordered w-full"
                                    value={editPriority}
                                    onChange={(e) => setEditPriority(e.target.value as TicketPriority)}
                                    disabled={busy}
                                >
                                    {(Object.keys(TICKET_PRIORITY_LABELS) as TicketPriority[]).map((p) => (
                                        <option key={p} value={p}>
                                            {TICKET_PRIORITY_LABELS[p]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="flex flex-col gap-1 mt-2">
                            <label className="label py-1">
                                <span className="text-sm font-medium">Popis</span>
                            </label>
                            <textarea
                                className="textarea textarea-bordered w-full min-h-32"
                                value={editDescription}
                                onChange={(e) => setEditDescription(e.target.value)}
                                maxLength={10000}
                                disabled={busy}
                            />
                        </div>
                        <div className="flex justify-end gap-2 mt-2">
                            <button
                                type="button"
                                className="btn btn-ghost btn-sm"
                                onClick={handleCancelEdit}
                                disabled={busy}
                            >
                                <X size={14} /> Zrušit
                            </button>
                            <button
                                type="button"
                                className="btn btn-primary btn-sm"
                                onClick={handleSaveEdit}
                                disabled={busy || !editTitle.trim() || !editDescription.trim()}
                            >
                                {busy && <span className="loading loading-spinner loading-xs" />}
                                <Check size={14} /> Uložit změny
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Metadata */}
            <div className="card bg-base-100 shadow-sm">
                <div className="card-body p-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div>
                            <span className="text-base-content/60">Vytvořil:</span>{' '}
                            <span className="font-medium">{ticket.creator?.name ?? '—'}</span>
                            {ticket.creator?.email && (
                                <span className="text-base-content/60"> ({ticket.creator.email})</span>
                            )}
                        </div>
                        <div>
                            <span className="text-base-content/60">Organizace:</span>{' '}
                            <span className="font-medium">{ticket.organization?.name ?? '—'}</span>
                        </div>
                        <div>
                            <span className="text-base-content/60">Vytvořeno:</span>{' '}
                            <span className="font-medium">{formatDate(ticket.created_at)}</span>
                        </div>
                        {ticket.closed_at && (
                            <div>
                                <span className="text-base-content/60">Zavřeno:</span>{' '}
                                <span className="font-medium">{formatDate(ticket.closed_at)}</span>
                            </div>
                        )}
                        {ticket.page_url && (
                            <div className="md:col-span-2">
                                <span className="text-base-content/60">URL stránky:</span>{' '}
                                <code className="text-xs bg-base-200 px-1.5 py-0.5 rounded">
                                    {ticket.page_url}
                                </code>
                            </div>
                        )}
                        {ticket.viewport && (
                            <div>
                                <span className="text-base-content/60">Viewport:</span>{' '}
                                <span className="font-mono text-xs">{ticket.viewport}</span>
                            </div>
                        )}
                        {ticket.user_agent && (
                            <div className="md:col-span-2">
                                <span className="text-base-content/60">User-Agent:</span>{' '}
                                <span className="text-xs text-base-content/70 break-all">
                                    {ticket.user_agent}
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Popis */}
            <div className="card bg-base-100 shadow-sm">
                <div className="card-body p-4">
                    <h2 className="card-title text-lg">Popis</h2>
                    <p className="whitespace-pre-wrap text-sm text-base-content/80">
                        {ticket.description}
                    </p>
                </div>
            </div>

            {/* Přílohy */}
            <div className="card bg-base-100 shadow-sm">
                <div className="card-body p-4">
                    <div className="flex items-center justify-between gap-2">
                        <h2 className="card-title text-lg">
                            Přílohy ({ticket.attachments?.length ?? 0})
                        </h2>
                        {/* Přidat další přílohu (jen tvůrce/superadmin) */}
                        {canUpdate && (
                            <>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    className="hidden"
                                    onChange={handleAttachmentUpload}
                                    disabled={busy}
                                />
                                <button
                                    type="button"
                                    className="btn btn-sm btn-ghost gap-1"
                                    onClick={() => fileInputRef.current?.click()}
                                    disabled={busy}
                                >
                                    <Upload size={14} /> Přidat
                                </button>
                            </>
                        )}
                    </div>
                    {ticket.attachments && ticket.attachments.length > 0 ? (
                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mt-2">
                            {ticket.attachments.map((att) => {
                                const Icon = getAttachmentIcon(att.mime_type);
                                return (
                                    <div
                                        key={att.uuid}
                                        className="relative overflow-hidden rounded-lg border border-base-300 hover:border-primary transition-colors group"
                                    >
                                        <button
                                            type="button"
                                            onClick={() => setLightbox(att)}
                                            className="block w-full text-left"
                                            title={att.filename}
                                        >
                                            {isImageMime(att.mime_type) ? (
                                                <img
                                                    src={att.signed_url}
                                                    alt={att.filename}
                                                    className="w-full h-32 object-cover"
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <div className="w-full h-32 flex items-center justify-center bg-base-200">
                                                    <Icon size={48} className="text-base-content/60" />
                                                </div>
                                            )}
                                            <div className="px-2 py-1.5 bg-base-100 border-t border-base-300">
                                                <div className="text-xs font-medium truncate">
                                                    {att.filename}
                                                </div>
                                                <div className="text-xs text-base-content/60">
                                                    {formatSize(att.size_bytes)}
                                                </div>
                                            </div>
                                        </button>
                                        {canUpdate && (
                                            <button
                                                type="button"
                                                onClick={() => handleAttachmentDelete(att)}
                                                className="absolute top-1 right-1 btn btn-xs btn-circle btn-error opacity-0 group-hover:opacity-100 transition-opacity"
                                                title="Smazat přílohu" aria-label="Smazat přílohu"
                                                disabled={busy}
                                            >
                                                <Trash2 size={12} />
                                            </button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <p className="text-sm text-base-content/60 italic mt-2">
                            Žádné přílohy.
                        </p>
                    )}
                </div>
            </div>

            {/* Komentáře */}
            <div className="card bg-base-100 shadow-sm">
                <div className="card-body p-4">
                    <h2 className="card-title text-lg">
                        Komentáře ({ticket.comments?.length ?? 0})
                    </h2>
                    <TicketComments comments={ticket.comments ?? []} />
                    <div className="divider my-2"></div>
                    <TicketCommentComposer ticketUuid={ticket.uuid} />
                </div>
            </div>

            {/* Audit timeline (collapsed) */}
            {ticket.audit_log && ticket.audit_log.length > 0 && (
                <TicketAuditTimeline events={ticket.audit_log} />
            )}

            <TicketScreenshotLightbox attachment={lightbox} onClose={() => setLightbox(null)} />
        </div>
    );
}

export default TicketDetail;
