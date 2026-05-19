// Seznam komentářů + inline edit + delete.
// chat layout (vlastní vpravo, cizí vlevo) + anchor #comment-{uuid}.

import { router, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { cs } from 'date-fns/locale';
import { Pencil, Trash2, X, Check } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import type { TicketComment } from '../types';

interface TicketCommentsProps {
    comments: TicketComment[];
}

/**
 * Minimální tvar sdílených Inertia `auth` props, který balíček potřebuje.
 *
 * host `@/types` typ `Auth` se sem NEpřenáší (nese `User` model
 * specifický pro host aplikaci). Komponenta čte výhradně `auth.user.id` pro rozlišení vlastních
 * komentářů — host appka musí `auth.user.id` ve shared props poskytnout.
 */
interface PackageAuthProps {
    auth?: { user?: { id?: number } | null } | null;
}

// Vrátí iniciály z fullname (pro avatar fallback)
function initials(name: string | undefined): string {
    if (!name) return '?';
    return name
        .split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function formatTime(iso: string): string {
    try {
        return formatDistanceToNow(new Date(iso), { addSuffix: true, locale: cs });
    } catch {
        return iso;
    }
}

interface CommentItemProps {
    comment: TicketComment;
    // Zda komentář napsal aktuálně přihlášený uživatel (→ chat-end + barevná bublina)
    isOwn: boolean;
}

function CommentItem({ comment, isOwn }: CommentItemProps) {
    const [editing, setEditing] = useState(false);
    const [editBody, setEditBody] = useState(comment.body);
    const [busy, setBusy] = useState(false);

    const handleSave = () => {
        if (!editBody.trim() || busy) return;
        setBusy(true);
        router.patch(
            `/comments/${comment.uuid}`,
            { body: editBody.trim() },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditing(false);
                    toast.success('Komentář upraven');
                },
                onError: (errors) => {
                    const msg = errors.body ?? 'Úpravu se nepodařilo uložit (možná vypršelo 5 min okno)';
                    toast.error(typeof msg === 'string' ? msg : 'Chyba');
                },
                onFinish: () => setBusy(false),
            },
        );
    };

    const handleDelete = () => {
        if (!confirm('Opravdu smazat komentář? Akce je nevratná.')) return;
        setBusy(true);
        router.delete(`/comments/${comment.uuid}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Komentář smazán'),
            onError: () => toast.error('Smazání selhalo'),
            onFinish: () => setBusy(false),
        });
    };

    return (
        // Anchor pro notifikace cílící na /tickets/{uuid}#comment-{uuid}
        <div id={`comment-${comment.uuid}`} className={`chat ${isOwn ? 'chat-end' : 'chat-start'}`}>
            <div className="chat-image avatar avatar-placeholder">
                <div className="bg-neutral text-neutral-content rounded-full w-8 h-8">
                    <span className="text-xs">{initials(comment.author?.name)}</span>
                </div>
            </div>
            <div className="chat-header">
                <span className="font-medium">{comment.author?.name ?? '—'}</span>
                <time className="text-xs opacity-60 ml-2">{formatTime(comment.created_at)}</time>
                {comment.updated_at !== comment.created_at && (
                    <span className="text-xs opacity-40 ml-1 italic">(upraveno)</span>
                )}
            </div>

            {editing ? (
                // Editační režim — textarea + akce mimo bublinu kvůli kontrastu
                <div className="chat-bubble bg-base-200 text-base-content">
                    <textarea
                        className="textarea textarea-bordered w-full min-h-20 text-sm"
                        value={editBody}
                        onChange={(e) => setEditBody(e.target.value)}
                        maxLength={5000}
                        disabled={busy}
                    />
                    <div className="flex justify-end gap-1 mt-2">
                        <button
                            type="button"
                            className="btn btn-ghost btn-xs"
                            onClick={() => {
                                setEditing(false);
                                setEditBody(comment.body);
                            }}
                            disabled={busy}
                        >
                            <X size={12} /> Zrušit
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary btn-xs"
                            onClick={handleSave}
                            disabled={!editBody.trim() || busy}
                        >
                            <Check size={12} /> Uložit
                        </button>
                    </div>
                </div>
            ) : (
                <div className={`chat-bubble ${isOwn ? 'chat-bubble-primary' : ''}`}>
                    {/*
                      body_html je sanitized na BE.
                      Uvnitř chat-bubble NEpoužíváme `prose` barvy textu — vnutily by
                      tmavý text a rozbily kontrast v obarvené (primary) bublině.
                      Barva textu dědí z chat-bubble (-content varianta).
                    */}
                    <div
                        className="text-sm break-words [&_a]:underline [&_p]:my-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_code]:px-1 [&_code]:rounded [&_code]:bg-base-content/10"
                        dangerouslySetInnerHTML={{ __html: comment.body_html }}
                    />
                </div>
            )}

            {!editing && (comment.can_edit || comment.can_delete) && (
                <div className="chat-footer flex gap-1 mt-1">
                    {comment.can_edit && (
                        <button
                            type="button"
                            className="btn btn-ghost btn-xs"
                            onClick={() => setEditing(true)}
                            title="Upravit (do 5 min od vytvoření)"
                            disabled={busy}
                        >
                            <Pencil size={12} /> Upravit
                        </button>
                    )}
                    {comment.can_delete && (
                        <button
                            type="button"
                            className="btn btn-ghost btn-xs text-error"
                            onClick={handleDelete}
                            title="Smazat"
                            disabled={busy}
                        >
                            <Trash2 size={12} /> Smazat
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

export function TicketComments({ comments }: TicketCommentsProps) {
    // Aktuální uživatel ze sdílených Inertia props — host appka musí
    // `auth.user.id` v shared props poskytnout (viz PackageAuthProps).
    const auth = usePage().props.auth as PackageAuthProps['auth'];
    const currentUserId = auth?.user?.id;

    if (comments.length === 0) {
        return (
            <p className="text-sm text-base-content/60 italic">
                Zatím žádné komentáře. Buďte první.
            </p>
        );
    }

    return (
        <div className="space-y-1">
            {comments.map((c) => (
                <CommentItem
                    key={c.uuid}
                    comment={c}
                    isOwn={c.author?.id != null && c.author.id === currentUserId}
                />
            ))}
        </div>
    );
}

export default TicketComments;
