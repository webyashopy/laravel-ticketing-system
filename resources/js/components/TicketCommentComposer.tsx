// Composer pro nový komentář pod ticketem (Markdown).
// odeslání zkratkou Ctrl+Enter / Cmd+Enter.

import { router } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface TicketCommentComposerProps {
    ticketUuid: string;
}

const MAX_BODY_LENGTH = 5000;

export function TicketCommentComposer({ ticketUuid }: TicketCommentComposerProps) {
    const [body, setBody] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Odesílací logika bez závislosti na evente — volá ji submit i Ctrl+Enter.
    const submitComment = () => {
        const trimmed = body.trim();
        // Guardy: neprázdný obsah, ne over-limit, ne probíhající odeslání.
        if (!trimmed || trimmed.length > MAX_BODY_LENGTH || isSubmitting) return;

        setIsSubmitting(true);
        router.post(
            `/tickets/${ticketUuid}/comments`,
            { body: trimmed },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setBody('');
                    toast.success('Komentář přidán');
                },
                onError: (errors) => {
                    const msg = errors.body ?? 'Komentář se nepodařilo odeslat';
                    toast.error(typeof msg === 'string' ? msg : 'Chyba při odesílání');
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        submitComment();
    };

    // Ctrl+Enter (Windows/Linux) i Cmd+Enter (macOS) odešle komentář.
    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            submitComment();
        }
    };

    const remaining = MAX_BODY_LENGTH - body.length;
    const isOverLimit = remaining < 0;

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-1">
            <label className="label py-1">
                <span className="text-sm font-medium">Přidat komentář</span>
                <span className="text-xs">
                    Podporuje Markdown ({remaining} / {MAX_BODY_LENGTH} znaků)
                </span>
            </label>
            <textarea
                className={`textarea textarea-bordered w-full min-h-24 ${
                    isOverLimit ? 'textarea-error' : ''
                }`}
                placeholder="Napište komentář… (Markdown: **tučně**, *kurzíva*, `kód`, - seznam)"
                value={body}
                onChange={(e) => setBody(e.target.value)}
                onKeyDown={handleKeyDown}
                maxLength={MAX_BODY_LENGTH + 100}
                disabled={isSubmitting}
            />
            <div className="flex items-center justify-end gap-3 mt-2">
                <span className="text-xs text-base-content/50">Ctrl+Enter pro odeslání</span>
                <button
                    type="submit"
                    className="btn btn-primary btn-sm gap-1"
                    disabled={!body.trim() || isOverLimit || isSubmitting}
                >
                    {isSubmitting && <span className="loading loading-spinner loading-xs" />}
                    <Send size={14} />
                    Odeslat
                </button>
            </div>
        </form>
    );
}

export default TicketCommentComposer;
