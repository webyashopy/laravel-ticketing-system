// Modal pro vytvoření nového ticketu.
// Auto-vyplní page_url + viewport + UA z window.
// DocumentDropZone v staged režimu (upload až při submitu formu).
//
// Tlačítko "Udělat screenshot" + ScreenshotPicker overlay
// (generuje PNG, počítá se mezi standardní přílohy).
//
// Rozšířený whitelist: PDF/TXT/LOG/CSV/DOCX/XLSX/ZIP
// vedle obrázků; max 20 příloh (10 MB / kus).

import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Camera } from 'lucide-react';
import { toast } from 'sonner';

import type {
    TicketCategory,
    TicketCreateFormData,
    TicketPriority,
} from '../types';
import {
    TICKET_CATEGORY_LABELS,
    TICKET_PRIORITY_LABELS,
} from '../types';

import { Z_LAYERS } from '../lib/z-layers';

import { DocumentDropZone } from './DocumentDropZone';
import { ScreenshotPicker } from './ScreenshotPicker';

interface TicketCreateModalProps {
    open: boolean;
    onClose: () => void;
    onCreated?: () => void;
}

const MAX_ATTACHMENTS = 20;
const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024; // 10 MB
// Whitelist MIME typů — sjednoceno s config/tickets.php (defense in depth)
const ALLOWED_ATTACHMENT_MIMES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
    'application/pdf',
    'text/plain',
    'text/csv',
    'application/csv',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/zip',
    'application/x-zip-compressed',
];

/**
 * Flash prop balíčku sdílený middlewarem `ShareTicketsBadge`.
 * Zdroj dat pro toast po vytvoření ticketu.
 */
interface TicketsCreatedFlash {
    id: number;
    uuid: string;
    title: string;
    url: string;
}

/**
 * Toast po úspěšném vytvoření — s odkazem na nový ticket, pokud host
 * aplikace má zapojený `ShareTicketsBadge` middleware. Bez něj (nebo když
 * flash z jakéhokoli důvodu chybí) degraduje na prostou hlášku, takže
 * vytvoření ticketu nikdy nevypadá jako by selhalo.
 */
function showCreatedToast(page: { props?: Record<string, unknown> }): void {
    const flash = page?.props?.ticketsFlash as
        | { created?: TicketsCreatedFlash | null }
        | undefined;
    const created = flash?.created;

    if (!created?.url) {
        toast.success('Ticket byl úspěšně vytvořen');
        return;
    }

    toast.success(`Ticket #${created.id} byl vytvořen`, {
        description: created.title,
        action: {
            label: 'Zobrazit',
            onClick: () => router.visit(created.url),
        },
    });
}

const initialFormData: Omit<TicketCreateFormData, 'page_url' | 'viewport' | 'user_agent' | 'attachments'> = {
    title: '',
    description: '',
    category: 'bug',
    priority: 'medium',
};

export function TicketCreateModal({ open, onClose, onCreated }: TicketCreateModalProps) {
    const [formData, setFormData] = useState({ ...initialFormData });
    const [attachments, setAttachments] = useState<File[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    // Aktivní screenshot picker overlay (modal je schován přes hidden)
    const [pickerActive, setPickerActive] = useState(false);
    // Klíč pro vynucený remount DocumentDropZone po přidání screenshotu
    const [dropZoneKey, setDropZoneKey] = useState(0);

    // Reset stavu při otevření / zavření
    useEffect(() => {
        if (open) {
            setFormData({ ...initialFormData });
            setAttachments([]);
            setErrors({});
            setPickerActive(false);
            setDropZoneKey((k) => k + 1);
        }
    }, [open]);

    if (!open) return null;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        // Inertia router — backend vrací `back()`, uživatel tedy zůstává na
        // stránce, odkud bug hlásil (žádné přesměrování na detail ticketu).
        // forceFormData zajistí multipart/form-data kvůli přílohám (File[]).
        router.post(
            '/tickets',
            {
                title: formData.title,
                description: formData.description,
                category: formData.category,
                priority: formData.priority,
                // Auto-fill kontextu — page_url, viewport, user_agent z window
                page_url: window.location.pathname + window.location.search,
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                user_agent: navigator.userAgent,
                attachments,
            },
            {
                forceFormData: true,
                onSuccess: (page) => {
                    showCreatedToast(page);
                    onCreated?.();
                    onClose();
                },
                onError: (errs) => {
                    setErrors(errs);
                    toast.error('Zkontrolujte vyplněná pole');
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    /**
     * Callback z ScreenshotPickeru — přilepí PNG do attachments a zavře overlay.
     * Limit MAX_ATTACHMENTS hlídáme, aby user nepřesáhl backend whitelist.
     */
    const handleScreenshotCapture = (file: File) => {
        setAttachments((prev) => {
            if (prev.length >= MAX_ATTACHMENTS) {
                toast.error(`Maximum ${MAX_ATTACHMENTS} příloh — odeberte některou před přidáním další.`);
                return prev;
            }
            return [...prev, file];
        });
        // Vynucený remount DropZone aby se zobrazil nově přidaný soubor v jeho UI
        setDropZoneKey((k) => k + 1);
        setPickerActive(false);
        toast.success('Screenshot přidán mezi přílohy');
    };

    return (
        <>
            {/* Modal — schovaný přes display:none když je aktivní picker (form state zachován).
                zIndex nad FAB i nad modaly host aplikace (viz Z_LAYERS). */}
            <div
                className="modal modal-open"
                style={{
                    zIndex: Z_LAYERS.modal,
                    ...(pickerActive ? { display: 'none' } : {}),
                }}
            >
                <div className="modal-box max-w-2xl">
                    <h3 className="font-bold text-lg mb-4">Nový ticket</h3>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Title */}
                        <div className="flex flex-col gap-1">
                            <label className="label py-1 block">
                                <span className="text-sm font-medium">
                                    Název <span className="text-error">*</span>
                                </span>
                            </label>
                            <input
                                type="text"
                                className={`input input-bordered w-full ${errors.title ? 'input-error' : ''}`}
                                placeholder="Krátký popis problému"
                                value={formData.title}
                                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                required
                                maxLength={255}
                                autoFocus
                            />
                            {errors.title && (
                                <label className="label">
                                    <span className="text-xs text-error">{errors.title}</span>
                                </label>
                            )}
                        </div>

                        {/* Category + Priority */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="flex flex-col gap-1">
                                <label className="label py-1 block">
                                    <span className="text-sm font-medium">Kategorie</span>
                                </label>
                                <select
                                    className="select select-bordered w-full"
                                    value={formData.category}
                                    onChange={(e) =>
                                        setFormData({ ...formData, category: e.target.value as TicketCategory })
                                    }
                                >
                                    {(Object.keys(TICKET_CATEGORY_LABELS) as TicketCategory[]).map((cat) => (
                                        <option key={cat} value={cat}>
                                            {TICKET_CATEGORY_LABELS[cat]}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="label py-1 block">
                                    <span className="text-sm font-medium">Priorita</span>
                                </label>
                                <select
                                    className="select select-bordered w-full"
                                    value={formData.priority}
                                    onChange={(e) =>
                                        setFormData({ ...formData, priority: e.target.value as TicketPriority })
                                    }
                                >
                                    {(Object.keys(TICKET_PRIORITY_LABELS) as TicketPriority[]).map((prio) => (
                                        <option key={prio} value={prio}>
                                            {TICKET_PRIORITY_LABELS[prio]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Description */}
                        <div className="flex flex-col gap-1">
                            <label className="label py-1 block">
                                <span className="text-sm font-medium">
                                    Popis <span className="text-error">*</span>
                                </span>
                            </label>
                            <textarea
                                className={`textarea textarea-bordered w-full min-h-32 ${errors.description ? 'textarea-error' : ''}`}
                                placeholder="Co se stalo? Co očekáváte? Krok za krokem reprodukce…"
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                required
                                maxLength={10000}
                            />
                            {errors.description && (
                                <label className="label">
                                    <span className="text-xs text-error">{errors.description}</span>
                                </label>
                            )}
                        </div>

                        {/* Screenshot pickeru (samostatná akce, NE součást DropZone labelu) */}
                        <div className="flex flex-col gap-1">
                            <label className="label py-1 block">
                                <span className="text-sm font-medium">Screenshot stránky</span>
                            </label>
                            <button
                                type="button"
                                className="btn btn-sm btn-outline gap-1 self-start"
                                onClick={() => setPickerActive(true)}
                                disabled={attachments.length >= MAX_ATTACHMENTS}
                                title={
                                    attachments.length >= MAX_ATTACHMENTS
                                        ? `Maximum ${MAX_ATTACHMENTS} příloh`
                                        : 'Pořídit screenshot stránky a přidat do příloh'
                                }
                            >
                                <Camera size={14} />
                                Udělat screenshot
                            </button>
                        </div>

                        {/* Přílohy — DropZone v staged režimu. Výčet typů + limity řeší
                            DropZone hint (`acceptedTypesLabel`), label drží pouze název pole. */}
                        <div className="flex flex-col gap-1">
                            <label className="label py-1 block">
                                <span className="text-sm font-medium">Přílohy (volitelné)</span>
                            </label>
                            <DocumentDropZone
                                key={dropZoneKey}
                                mode="staged"
                                allowedMimes={ALLOWED_ATTACHMENT_MIMES}
                                acceptedTypesLabel="Obrázky, PDF, TXT/LOG/CSV, DOCX/XLSX, ZIP"
                                maxSize={MAX_ATTACHMENT_SIZE}
                                maxFiles={MAX_ATTACHMENTS}
                                showOcrCheckbox={false}
                                showMetadataInputs={false}
                                initialFiles={attachments}
                                onFilesChange={setAttachments}
                            />
                            {errors.attachments && (
                                <label className="label">
                                    <span className="text-xs text-error">{errors.attachments}</span>
                                </label>
                            )}
                        </div>

                        {/* Akce */}
                        <div className="modal-action">
                            <button
                                type="button"
                                className="btn btn-ghost"
                                onClick={onClose}
                                disabled={isSubmitting}
                            >
                                Zrušit
                            </button>
                            <button
                                type="submit"
                                className="btn btn-primary"
                                disabled={isSubmitting}
                            >
                                {isSubmitting && <span className="loading loading-spinner loading-sm" />}
                                Vytvořit
                            </button>
                        </div>
                    </form>
                </div>
                {/* Klik na backdrop zavře modal */}
                <button
                    type="button"
                    className="modal-backdrop"
                    onClick={onClose}
                    aria-label="Zavřít modal"
                >
                    close
                </button>
            </div>

            {/* Screenshot picker overlay (mimo modal) */}
            {pickerActive && (
                <ScreenshotPicker
                    onCapture={handleScreenshotCapture}
                    onCancel={() => setPickerActive(false)}
                    maxSize={MAX_ATTACHMENT_SIZE}
                />
            )}
        </>
    );
}

export default TicketCreateModal;
